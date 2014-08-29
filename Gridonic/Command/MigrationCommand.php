<?php

namespace Gridonic\Command;

use Knp\Command\Command as KnpCommand;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends KnpCommand
{
    public function configure()
    {
        $this
            ->setName('migration:migrate')
            ->setDescription('Migrates the database to the newest version.')
            ->setHelp(

<<<EOF
    The <info>migration:migrate</info> command brings the Migrations to the newest version.
    If the database is empty, it creates all the tables from the Migrations-Folder.
    If there is content in the database, it checks up the Migration-Number with the schema_version in the table "schema_version".
    <info>app/console migration:migrate</info>
EOF
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexApplication();

        /** @var \Gridonic\Migration\Manager $manager */
        $manager    = $app['migration'];

        if (!$manager->hasVersionInfo()) {
            $manager->createVersionInfo();
        }

        $res = $manager->migrate();

        switch($res) {
            case true:
                $output->writeln(sprintf('Successfully executed <info>%d</info> migration(s)!', $manager->getMigrationExecuted()));
                foreach ($manager->getMigrationInfos() as $info) {
                    $output->writeln(sprintf(' - <info>%s</info>', $info));
                }
                break;
            case null:
                $output->writeln('No migrations to execute, you are up to date!');
                break;
        }
    }
}