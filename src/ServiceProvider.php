<?php

namespace Fomvasss\MediaLibraryExtension;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/media-library-extension.php' => config_path('media-library-extension.php')
        ], 'config');

        $this->publishedMigrations();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-library-extension.php', 'media-library-extension');

        $this->app->singleton(MediaManager::class, function () {
            return new MediaManager();
        });

        $this->app->alias(MediaManager::class, 'media-manager');
    }

    protected function publishedMigrations()
    {
        if (! class_exists('AddFieldsMediaTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/add_fields_media_table.php.stub' => database_path('/migrations/'.$timestamp.'_add_fields_media_table.php'),
            ], 'migrations');
        }
    }
}
