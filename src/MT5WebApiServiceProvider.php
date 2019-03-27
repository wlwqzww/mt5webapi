<?php

namespace aemaddin\MT5WebApi;

use Illuminate\Support\ServiceProvider;

class MT5WebApiServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'aemaddin');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'aemaddin');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
         $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

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
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mt5webapi.php', 'mt5webapi');

        // Register the service the package provides.
        $this->app->singleton('mt5webapi', function ($app) {
            return new MT5WebApi;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mt5webapi'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/mt5webapi.php' => config_path('mt5webapi.php'),
        ], 'mt5webapi.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/aemaddin'),
        ], 'mt5webapi.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/aemaddin'),
        ], 'mt5webapi.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/aemaddin'),
        ], 'mt5webapi.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
