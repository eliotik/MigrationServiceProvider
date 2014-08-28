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

use Silex\Application;

/**
 * Custom TestCase class with useful basic functions
 *
 * @author Beat Temperli <beat@gridonic.ch>
 */
abstract class GridonicTestCase extends \PHPUnit_Framework_TestCase
{
    protected $migrationPath = '/../Ressources/migrations';

    /**
     * Creates the silex app.
     *
     * @return Application
     */
    public function createApplication()
    {
        // open each time a new database
        $this->clearDatabase();

        /** @var Application $app */
        $app = new Application();

        // add config file
        require __DIR__ . '/../../config.test.php';


        // add this for the tests. Fails otherwise.
        $app['migration.path'] = __DIR__ . '/../Ressources/migrations';


        // return the created app.
        return $app;
    }

    private function clearDatabase() {
        $databaseDir = __DIR__ . '/../../database/';
        if (is_file($databaseDir . 'test.db')) {
            unlink($databaseDir . 'test.db');
        }
        @chmod($databaseDir, 0777);
    }
}