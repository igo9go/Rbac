<?php

namespace Gundy\Rbac;

use Illuminate\Support\ServiceProvider;

class RbacServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->loadViewsFrom(__DIR__ . '/views', 'rbac');

        $this->commands('command.permission.generate');

        $this->publishes([
            __DIR__ . '/views' => base_path('resources/views/vendor/rbac'),
            __DIR__ . '/config/rbac.php' => config_path('rbac.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRbac();

        $this->registerCommands();

        $this->mergeConfig();
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    public function registerRbac()
    {
        $this->app->bind('rbac', function ($app) {
            return new Entrust($app);
        });

        $this->app->alias('rbac', 'Gundy\Rbac\Rbac');
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->singleton('command.permission.generate', function ($app) {
            return new PermissionGenerateCommand($app['router']);
        });
    }

    /**
     * Merges user's and entrust's configs.
     *
     * @return void
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/rbac.php', 'options'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.permission.generate'];
    }
}
