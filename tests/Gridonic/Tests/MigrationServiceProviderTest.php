<?php

/*
 * This file is part of the MigrationServiceProvider.
 *
 * (c) Gridonic <hello@gridonic.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gridonic\Tests;

use Silex\Provider\DoctrineServiceProvider;
use Gridonic\Provider\MigrationServiceProvider;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for the MigrationServiceProvider
 *
 * @author Beat Temperli <beat@gridonic.ch>
 */
class MigrationServiceProviderTest extends GridonicTestCase
{

    protected $migrationCommand = 'migration:migrate';
    protected $databaseInsert = array(
        'test_id' => '1',
        'test_created' => '100000000',
        'test_name' => 'testname',
        'test_password' => '1234'
    );

    /**
     * Check some basic stuff.
     */
    public function testBasics() {

        // Are the tests running correct?
        $this->assertTrue(true);

        // Is everything correct with our created Application?
        $app = $this->createApplication();
        $this->assertInstanceOf('Silex\Application', $app);
    }

    /**
     * Test register migrations.
     */
    public function testRegisterMigration()
    {
        $app = $this->createApplication();

        $app->register(new DoctrineServiceProvider());
        $app->register(new MigrationServiceProvider(array(
            'migration.path' => $this->migrationPath,
        )));

        $this->assertInstanceOf('Gridonic\Migration\Manager', $app['migration']);

        /** @var \Gridonic\Migration\Manager $migration */
        $migration = $app['migration'];

        $this->assertEmpty($migration->getMigrationInfos());
    }

    /**
     * Test usage of migrations
     */
    public function testMigrations()
    {
        $app = $this->createApplication();

        $app->register(new DoctrineServiceProvider());
        $app->register(new MigrationServiceProvider(array(
            'migration.path' => $this->migrationPath,
        )));

        $this->assertInstanceOf('Gridonic\Migration\Manager', $app['migration']);

        /** @var \Gridonic\Migration\Manager $migration */
        $migration = $app['migration'];

        if (!$migration->hasVersionInfo()) {
            $migration->createVersionInfo();
        }
        $this->assertEmpty($migration->getMigrationInfos());

        // do the migration
        $migration->migrate();

        $migrationInformation = $migration->getMigrationInfos();

        $this->assertCount(1, $migrationInformation);

        $this->assertEquals('Added a test table', $migrationInformation[1]);

        $this->assertEmpty(!$migrationInformation);

        // do the migration again.
        $migration->migrate();

        // now nothing should have changed.
        $this->assertEquals($migrationInformation, $migration->getMigrationInfos());
    }

    /**
     * Test the migration command migration:migrate
     * - Execute, check output
     * - Execute again, check output
     * - Insert a row in the migrated table
     */
    public function testMigrationCommand()
    {
        /** @var \Knp\Console\Application $app */
        $app = $this->createConsoleApplication();

        $expectedMigrationMessageSuccess = "Successfully executed 1 migration(s)!\n - Added a test table\n";
        $expectedMigrationMessageFailed = "No migrations to execute, you are up to date!\n";

        // get migrationCommand
        $command = $app->get($this->migrationCommand);
        $tester = new CommandTester($command);

        // execute first time
        $tester->execute(array(
            'command' => $command->getName(),
        ));

        // should be successfully
        $this->assertEquals($expectedMigrationMessageSuccess, $tester->getDisplay());

        // execute second time
        $tester->execute(array(
            'command' => $command->getName(),
        ));

        // should be fail
        $this->assertEquals($expectedMigrationMessageFailed, $tester->getDisplay());

        // test content of database
        $silexApp = $app->getSilexApplication();

        /** @var \Doctrine\DBAL\Connection $db */
        $db =$silexApp['db'];

        $db->insert('test', $this->databaseInsert);

        $result = $db->fetchAssoc("SELECT `test_name` FROM `test` WHERE `test_id` = 1");

        $this->assertEquals($this->databaseInsert['test_name'], $result['test_name']);
    }
}