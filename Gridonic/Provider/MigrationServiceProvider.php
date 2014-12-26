<?php

namespace Gridonic\Provider;

use Symfony\Component\Finder\Finder;
use Silex\ServiceProviderInterface;
use Silex\Application;
use Gridonic\Migration\Manager as MigrationManager;
use Gridonic\Console\ConsoleEvents;
use Gridonic\Console\ConsoleEvent;
use Gridonic\Command\MigrationCommand;

/**
 * Migration service provider
 *
 * @package Gridonic\Provider
 */
class MigrationServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['migration'] = $app->share(function () use ($app) {
            return new MigrationManager($app['db'], $app, Finder::create()->in($app['migration.path']));
        });

        $app['dispatcher']->addListener(ConsoleEvents::INIT, function (ConsoleEvent $event) {
            $application = $event->getApplication();
            $application->add(new MigrationCommand());
        });
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
        if (isset($app['migration.register_before_handler']) && $app['migration.register_before_handler']) {
            $this->registerBeforeHandler($app);
            return;
        }

        if (isset($app['twig'])) {
            $app['twig']->addGlobal('migration_infos', 'You have to start the migration manually in the console.');
        }
    }

    /**
     * Register the before request event handler and add migration infos.
     *
     * @param Application $app The Silex application instance
     */
    private function registerBeforeHandler(Application $app)
    {
        $app->before(function () use ($app) {
            $manager = $app['migration'];

            if (!$manager->hasVersionInfo()) {
                $manager->createVersionInfo();
            }

            $migrate = $manager->migrate();

            if (isset($app['twig'])) {
                $migrationInfos = $manager->getMigrationInfos();
                $migrationVersion = $manager->getCurrentVersion();

                if (true === $migrate) {
                    $app['twig']->addGlobal('migration_infos', 'Migrated. New version: ' . $migrationVersion . '. Status: ' . $migrationInfos[$migrationVersion]);
                    return;
                }

                $app['twig']->addGlobal('migration_infos', 'Nothing to migrate. Actual version: ' . $migrationVersion);
            }
        });
    }
}
