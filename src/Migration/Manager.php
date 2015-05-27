<?php

namespace AchrafSoltani\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\Finder;
use Silex\Application;

/**
 * Migration manager class
 *
 * @package AchrafSoltani\Migration
 */
class Manager
{
    const COLUMN_NAME_VERSION = 'schema_version';

    private $application;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    private $schema;

    /**
     * Clone from $schema
     * We need this schema to migrate the difference from schema to schemaUp
     * @var \Doctrine\DBAL\Schema\Schema
     */
    private $toSchemaUp;

    /**
     * Clone from $schemaUp
     * We need this schema to migrate the difference from schemaUp to schemaDown
     * @var \Doctrine\DBAL\Schema\Schema
     */
    private $toSchemaDown;

    private $connection;

    private $currentVersion = null;

    private $migrationInfos = array();

    private $migrationExecuted = 0;

    private $migrationsTableName = 'schema_version';

    /**
     * Public constructor
     *
     * @param Connection $connection
     * @param Application $application
     * @param Finder $finder
     */
    public function __construct(Connection $connection, Application $application, Finder $finder)
    {
        $this->schema       = $connection->getSchemaManager()->createSchema();
        $this->toSchemaUp   = clone($this->schema);
        $this->toSchemaDown = clone($this->toSchemaUp);
        $this->connection   = $connection;
        $this->finder       = $finder;
        $this->application  = $application;

        if (isset($application['migration.migrations_table_name'])) {
            $this->migrationsTableName = $application['migration.migrations_table_name'];
        }
    }

    /**
     * TODO: Document
     * @param Schema $fromSchema
     * @param Schema $toSchema
     */
    private function buildSchema(Schema $fromSchema, Schema $toSchema)
    {
        $queries = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $this->connection->exec($query);
        }
    }

    /**
     * TODO: Document
     * @param $from
     * @return array
     */
    private function findMigrations($from)
    {
        $finder = clone($this->finder);
        $migrations = array();

        $finder
            ->files()
            ->name('*Migration.php')
            ->sortByName();

        foreach ($finder as $migration) {
            if (preg_match('/^(\d+)_(.*Migration).php$/', basename($migration), $matches)) {
                list(, $version, $class) = $matches;

                if ((int) ltrim($version, 0) > $from) {
                    $ns = require_once $migration; 
                    
                    if(empty($ns))
                        $ns = '\\Migration';

                    $fqcn = $ns.'\\'.$class;

                    if (!class_exists($fqcn)) {
                        throw new \RuntimeException(sprintf('Could not find class "%s" in "%s"', $fqcn, $migration));
                    }

                    $migrations[] = new $fqcn();
                }
            }
        }

        return $migrations;
    }

    /**
     * TODO: Document
     */
    private function actualizeSchema()
    {
        $this->schema = $this->connection->getSchemaManager()->createSchema();
        $this->toSchemaUp = clone($this->schema);
        $this->toSchemaDown = clone($this->toSchemaUp);
    }

    /**
     * TODO: Document
     * @return array
     */
    public function getMigrationInfos()
    {
        return $this->migrationInfos;
    }

    /**
     * TODO: Document
     * @return int
     */
    public function getMigrationExecuted()
    {
        return $this->migrationExecuted;
    }

    /**
     * TODO: Document
     * @return mixed|null
     */
    public function getCurrentVersion()
    {
        if (is_null($this->currentVersion)) {
            $this->currentVersion = $this->connection->fetchColumn('SELECT ' . self::COLUMN_NAME_VERSION . ' FROM ' . $this->migrationsTableName);
        }

        return $this->currentVersion;
    }

    /**
     * TODO: Document
     * @param $version
     */
    public function setCurrentVersion($version)
    {
        $this->currentVersion = $version;
        $this->connection->executeUpdate('UPDATE ' . $this->migrationsTableName . ' SET ' . self::COLUMN_NAME_VERSION . ' = ?', array($version));
    }

    /**
     * TODO: Document
     * @return bool
     */
    public function hasVersionInfo()
    {
        return $this->schema->hasTable($this->migrationsTableName);
    }

    /**
     * TODO: Document
     */
    public function createVersionInfo()
    {
        $schema = clone($this->schema);

        $schemaVersion = $schema->createTable($this->migrationsTableName);
        $schemaVersion->addColumn(self::COLUMN_NAME_VERSION, 'integer', array('unsigned' => true, 'default' => 0));

        $this->buildSchema($this->schema, $schema);

        $this->connection->insert($this->migrationsTableName, array(self::COLUMN_NAME_VERSION => 0));
    }

    /**
     * TODO: Document
     * @return bool|null
     */
    public function migrate()
    {
        $from = $this->connection->fetchColumn('SELECT ' . self::COLUMN_NAME_VERSION . ' FROM ' . $this->migrationsTableName);
        $migrations = $this->findMigrations($from);

        if (count($migrations) == 0) {
            return null;
        }

        $migrationInfos = array();

        /** @var $migration \AchrafSoltani\Migration\AbstractMigration */
        foreach ($migrations as $migration) {
            $this->actualizeSchema();

            // schema up, edit database
            $migration->schemaUp($this->toSchemaUp);

            // build app to include the changes
            $this->buildSchema($this->schema, $this->toSchemaUp);

            // app up, edit content
            $migration->appUp($this->application);

            // actualize the schema to the newest database-schema.
            $this->actualizeSchema();

            // schema down, edit database
            $migration->schemaDown($this->toSchemaDown);

            // build app to include the changes
            $this->buildSchema($this->toSchemaUp, $this->toSchemaDown);

            // app down, edit content
            $migration->appDown($this->application);

            if (null !== $migration->getMigrationInfo()) {
                $migrationInfos[$migration->getVersion()] = $migration->getMigrationInfo();
            }

            $this->migrationExecuted++;
        }

        $this->migrationInfos = $migrationInfos;

        $this->setCurrentVersion($migration->getVersion());

        return true;
    }
}
