<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseSafety;

class TestDatabaseSafetyTest extends TestCase
{
    /** @return array<string, array{string, string, string}> */
    public static function unsafeConfigurations(): array
    {
        return [
            'base real' => ['testing', 'pgsql', 'Parcial2'],
            'entorno local' => ['local', 'pgsql', 'Parcial2_test'],
            'sqlite' => ['testing', 'sqlite', 'Parcial2_test'],
            'sufijo incompleto' => ['testing', 'pgsql', 'Parcial2_test_backup'],
            'nombre vacío' => ['testing', 'pgsql', ''],
        ];
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_refuses_unsafe_configuration_before_migrations(string $environment, string $driver, string $database): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pruebas bloqueadas');

        TestDatabaseSafety::assertConfiguration($environment, $driver, $database);
    }

    public function test_accepts_the_isolated_postgresql_database(): void
    {
        TestDatabaseSafety::assertConfiguration('testing', 'pgsql', 'Parcial2_test');

        $this->addToAssertionCount(1);
    }
}
