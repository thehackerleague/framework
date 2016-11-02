<?php

namespace Mods\Theme;

use Mods\Support\ServiceProvider;
use Mods\Theme\Console\ThemeDeployCommand;
use Mods\Theme\Console\ThemeClearCommand;
use Mods\Theme\Console\ThemePreProcessCommand;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
        $this->app->singleton('command.theme.clear', function ($app) {
            return new ThemeClearCommand($app['files']);
        });
        $this->app->singleton('command.theme.preprocessor', function ($app) {
            return new ThemePreProcessCommand($app['files']);
        });
        $this->commands(['command.theme.deploy', 'command.theme.clear', 'command.theme.preprocessor']);

        $this->app->singleton('theme.deployer', function ($app) {
            return new Deployer($app['files'], $app['theme.asset.resolver'], $app['config']);
        });

        $this->app->singleton('theme.preprocessor', function ($app) {
            return new PreProcess(
                $app, 
                $app['files'],
                $app['Mods\View\Factory'], 
                $app['theme.resolver'],
                $app['config']
            );
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
            return ['command.theme.deploy', 'command.theme.clear'];
        }
    }
}
