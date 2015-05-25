<?php

namespace AchrafSoltani\Provider;

use Symfony\Component\Finder\Finder;
use Silex\ServiceProviderInterface;
use Silex\Application;
use AchrafSoltani\Migration\Manager as MigrationManager;
use Gridonic\Console\ConsoleEvents;
use Gridonic\Console\ConsoleEvent;
use AchrafSoltani\Command\MigrationCommand;

/**
 * Migration service provider
 *
 * @package AchrafSoltani\Provider
 */
class MigrationServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        
        
        $app['migration'] = $app->share(function () use ($app) {
            $app['db'] = !empty($app['db']) ? $app['db'] : $app['migration.db'];
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
        if (isset($app['migration.register_before_handler']) && true === $app['migration.register_before_handler']) {
            $this->registerBeforeHandler($app);
            return;
        }

        if (isset($app['twig'])) {
            $app['twig']->addGlobal('migration_infos', 'Before handler not registered. You have to start the migration manually in the console.');
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

            $migrationResult = $manager->migrate();

            if (isset($app['twig'])) {
                $migrationInfos = $manager->getMigrationInfos();
                $migrationVersion = $manager->getCurrentVersion();

                if (true === $migrationResult) {
                    $app['twig']->addGlobal('migration_infos', sprintf('Migrated. New version: %s. Status: %s', $migrationVersion, $migrationInfos[$migrationVersion]));
                    return;
                }

                $app['twig']->addGlobal('migration_infos', sprintf('Nothing to migrate. Actual version: %s', $migrationVersion));
            }
        });
    }
}
