# MigrationServiceProvider

[![Build Status](https://travis-ci.org/gridonic/MigrationServiceProvider.svg?branch=master)](https://travis-ci.org/gridonic/MigrationServiceProvider)

This is a simple homebrew schema migration system for silex and doctrine.

## Install

As usual, just include `gridonic/migration-service-provider` in your `composer.json` and register the service.

```php
$app->register(new \Gridonic\Provider\MigrationServiceProvider(), array(
    'migration.path' => __DIR__.'/../src/Resources/migrations',
    'migration.register_before_handler' => true,
    'migration.migrations_table_name'   => 'migration_version',
));
```

| Key | Type | Optional | Description |
| --- | --- | --- | --- |
| `migrations.path` | String, folder path | - | Where are your migrations? |
| `migrations.register_before_handler` | Boolean | x | Should the service run the migrations on each boot? |
| `migrations.migrations_table_name` | String | x | The name of the table in the database, where the migration_version is safed. Default `schema_version` |

## Enough small talk, I want to write migrations!

Perhaps, this documentation is not complete. So here are some links with more informations:

* [The official documentation for Doctrine's DBAL Schema Manager](http://readthedocs.org/docs/doctrine-dbal/en/latest/reference/schema-manager.html)
* [Original `KnpLabs\migration-service-provider`](https://github.com/KnpLabs/MigrationServiceProvider)

## Running migrations

There are two ways of running migrations

### Using the `before` handler

If you pass a `migration.register_before_handler` (set to `true`) when registering the service, then a `before` handler will be registered for migration to be run. It means that the migration manager will be run for each hit to your application.

You might want to enable this behavior for development mode, but please don't do that in production!

### Using the `migration:migrate` command

If you installed the console service provider right, you can use the `migration:migrate` command, so your app does not have to run the migrations each time when your web-Application is called.

## Writing migrations

A migration consist of a single file, holding a migration class. By design, the migration file must be named something like `<version>_<migration_name>Migration.php` and located in `src/Resources/migrations`, and the class `<migration_name>Migration`. For example, if your migration adds a `bar` field to the `foo` table, and is the 5th migration of your schema, you should name your file `05_FooBarMigration.php`, and the class would be named `FooBarMigration`.

In addition to these naming conventions, your migration class must extends `Gridonic\Migration\AbstractMigration`, which provides a few helping method such as `getVersion` and default implementations for migration methods.

The migration methods consist of 4 methods, which are called in this order:

* `schemaUp`
* `appUp`
* `schemaDown`
* `appDown`

### schemaUp
You get a `Doctrine\DBAL\Schema\Schema` instance where you can add, remove or modify the schema of your database.

### appUp
After the `schemaUp`, you can edit the application - you get a `Silex\Application` instance for that. Here you can modify existing data after you have added a column.

### schemaDown
After the `appUp`, you can modify the schema of your database again. You get a `Doctrine\DBAL\Schema\Schema` instance which you can use.

### appDown
Last but not least, you can work again with a `Silex\Application` instance. Modify the existing data or something like this.

## Migration infos

There's one last method you should know about: `getMigrationInfos`. This method should return a self-explanatory description of the migration (it is optional though, and you can skip its implementation).
If you use [Twig](http://twig.sensiolabs.org/), we have built in a `migration_infos` for twig - perhaps a function just for the developer-mode.

You can then use it with something like that:

```html
      Migration informations: {{ migration_infos }}
```

## Licence
The MigrationServiceProvider is licensed under the MIT license.
The original library from is taken from the [KnpLabs](https://github.com/KnpLabs/MigrationServiceProvider).