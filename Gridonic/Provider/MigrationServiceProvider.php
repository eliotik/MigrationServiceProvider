<?php

namespace Gridonic\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

use Gridonic\Migration\Manager as MigrationManager;

use Symfony\Component\Finder\Finder;

use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

use Gridonic\Command\MigrationCommand;

class MigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['migration'] = $app->share(function() use ($app) {
            return new MigrationManager($app['db'], $app, Finder::create()->in($app['migration.path']));
        });

        $app['dispatcher']->addListener(ConsoleEvents::INIT, function(ConsoleEvent $event) {
            $application = $event->getApplication();
            $application->add(new MigrationCommand());
        });
    }

    public function boot(Application $app)
    {
        if (isset($app['migration.register_before_handler']) && $app['migration.register_before_handler']) {
            $this->registerBeforeHandler($app);
        }else {
            $app['twig']->addGlobal('migration_infos', 'You have to start the migration manually in the console.');
        }
    }

    private function registerBeforeHandler($app)
    {
        $app->before(function() use ($app) {
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
                } else {
                    $app['twig']->addGlobal('migration_infos', 'Nothing to migrate. Actual version: ' . $migrationVersion);
                }
            }
        });
    }
}
