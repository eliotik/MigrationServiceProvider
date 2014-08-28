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

/**
 * Tests for the MigrationServiceProvider
 *
 * @author Beat Temperli <beat@gridonic.ch>
 */
class MigrationServiceProviderTest extends GridonicTestCase
{

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
}