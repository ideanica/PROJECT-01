<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app->make('db')->connection()->getConfig();

        // Stop before RefreshDatabase can migrate or erase an application database.
        if (! $app->environment('testing')
            || $connection['driver'] !== 'sqlite'
            || $connection['database'] !== ':memory:') {
            throw new \RuntimeException('Tests require the testing environment and an in-memory SQLite database.');
        }

        return $app;
    }
}
