<?php

namespace Tbtop\SpatieMediaLibrary\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Tbtop\Admin\AdminServiceProvider;
use Tbtop\SpatieMediaLibrary\SpatieMediaLibraryServiceProvider;

class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            AdminServiceProvider::class,
            MediaLibraryServiceProvider::class,
            SpatieMediaLibraryServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('filesystems.disks.public.driver', 'local');
    }

    // spatie/laravel-package-tools only auto-loads a package migration when the
    // consuming app opts in via runsMigrations(); tests load it explicitly instead.
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}
