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
            __DIR__.'/../config/medialibrary-extension.php' => config_path('medialibrary-extension.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/medialibrary-extension.php', 'medialibrary-extension');

        $this->app->singleton(MediaLibraryManager::class, function () {
            return new MediaLibraryManager();
        });

        $this->app->alias(MediaLibraryManager::class, 'medialibrary-manager');
    }
}
