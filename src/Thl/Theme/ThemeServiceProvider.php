<?php

namespace Thl\Theme;

use Thl\Support\ServiceProvider;
use Thl\Theme\Console\ThemeDeployCommand;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerThemeDeployer();

        $this->app->singleton('theme.asset.resolver', function ($app) {
            return new AssetResolver($app['files']);
        });

        $this->app->singleton('theme.resolver', function ($app) {
            return new ThemeResolver($app['files'], $app['config']);
        });

        $this->app->singleton('theme', function ($app) {
            $env = new  Factory(
                $app['theme.resolver'],
                $app['view'],
                $app['events']
            );
            $env->setContainer($app);
            return $env;
        });

        $this->app->booted(function ($app) {
            $app['theme']->booted($app);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerThemeDeployer()
    {
        $this->app->singleton('command.theme.deploy', function ($app) {
            return new ThemeDeployCommand($app['files']);
        });
        $this->commands(['command.theme.deploy']);

        $this->app->singleton('theme.deployer', function ($app) {
            return new Deployer($app['files'], $app['theme.asset.resolver'], $app['config']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        if (!$this->app->environment('production')) {
            return ['command.theme.deploy'];
        }
    }
}
