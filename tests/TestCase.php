<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\Support\TestDatabaseSafety;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        TestDatabaseSafety::assertApplication($app);

        if (isset($this->traitsUsedByTest[DatabaseMigrations::class])
            || (isset($this->traitsUsedByTest[RefreshDatabase::class]) && ! RefreshDatabaseState::$migrated)) {
            TestDatabaseSafety::resetSchema($app);
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}
