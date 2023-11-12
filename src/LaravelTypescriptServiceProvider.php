<?php

namespace Wovosoft\LaravelTypescript;

use Illuminate\Support\ServiceProvider;
use Wovosoft\LaravelTypescript\Console\Commands\LaravelTypescriptCommand;

class LaravelTypescriptServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-typescript.php', 'laravel-typescript');

        // Register the service the package provides.
        $this->app->singleton('laravel-typescript', function ($app) {
            return LaravelTypescript::new();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['laravel-typescript'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/laravel-typescript.php' => config_path('laravel-typescript.php'),
        ], 'laravel-typescript.config');

        // Registering package commands.
        $this->commands([
            LaravelTypescriptCommand::class,
        ]);
    }
}
