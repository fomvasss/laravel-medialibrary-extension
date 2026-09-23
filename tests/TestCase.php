<?php

declare(strict_types=1);

namespace Fomvasss\MediaLibraryExtension\Tests;

use Fomvasss\MediaLibraryExtension\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MediaLibraryServiceProvider::class, ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('media-library.disk_name', 'public');
        $app['config']->set('media-library.queue_conversions_by_default', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        (include __DIR__.'/../vendor/spatie/laravel-medialibrary/database/migrations/create_media_table.php.stub')->up();

        foreach (['add_fields_media_table', 'create_media_temporaries_table'] as $stub) {
            $this->migration($stub)->up();
        }

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * Stubs are either named classes (declared once per process) or anonymous ones
     */
    private function migration(string $stub): object
    {
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $stub)));

        if (class_exists($class, false)) {
            return new $class;
        }

        $migration = require __DIR__."/../database/migrations/{$stub}.php.stub";

        return is_object($migration) ? $migration : new $class;
    }
}
