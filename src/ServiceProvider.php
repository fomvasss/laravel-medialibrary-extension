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
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-library-extension.php', 'media-library-extension');

        $this->app->singleton(MediaLibraryManager::class, function () {
            return new MediaLibraryManager();
        });

        $this->app->alias(MediaLibraryManager::class, 'media-library-manager');
    }
}
