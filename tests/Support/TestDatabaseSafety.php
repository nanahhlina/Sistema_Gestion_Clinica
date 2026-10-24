<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class TestDatabaseSafety
{
    public static function assertConfiguration(string $environment, string $driver, string $database): void
    {
        if ($environment !== 'testing' || $driver !== 'pgsql' || ! str_ends_with($database, '_test')) {
            throw new RuntimeException('Pruebas bloqueadas: se exige APP_ENV=testing, PostgreSQL y una base terminada en _test.');
        }
    }

    public static function assertApplication(Application $app): void
    {
        if ($app->configurationIsCached()) {
            throw new RuntimeException('Pruebas bloqueadas: elimine la configuración en caché antes de ejecutar migraciones de prueba.');
        }

        $connection = $app->make('db')->connection();
        self::assertConfiguration($app->environment(), $connection->getDriverName(), $connection->getDatabaseName());
        $actualDatabase = $connection->selectOne('select current_database() as name')->name;
        self::assertConfiguration($app->environment(), $connection->getDriverName(), $actualDatabase);

        if ($actualDatabase !== $connection->getDatabaseName()) {
            throw new RuntimeException('Pruebas bloqueadas: la base conectada difiere de la configuración.');
        }
    }

    public static function resetSchema(Application $app): void
    {
        self::assertApplication($app);

        $app->make('db')->connection()->unprepared('DROP SCHEMA IF EXISTS vida_salud CASCADE; CREATE SCHEMA vida_salud');
    }
}
