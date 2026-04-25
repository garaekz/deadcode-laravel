<?php

declare(strict_types=1);

namespace Oxhq\Oxcribe\Tests;

use Deadcode\DeadcodeServiceProvider;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Register the package provider through its public Deadcode identity.
     */
    protected function getPackageProviders($app): array
    {
        return [
            DeadcodeServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
