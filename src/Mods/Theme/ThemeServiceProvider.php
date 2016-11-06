<?php

namespace Mods\Theme;

use Mods\Support\ServiceProvider;
use Mods\Theme\Console\Command;
use Mods\Theme\Console\Deployer;
use Mods\Theme\Console\PreProcess;
use Mods\Theme\Console\Complier;

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
            return new Command\Deploy($app['files']);
        });
        $this->app->singleton('command.theme.clear', function ($app) {
            return new Command\Clear($app['files']);
        });
        $this->app->singleton('command.theme.preprocessor', function ($app) {
            return new Command\PreProcess($app['files']);
        });
        $this->app->singleton('command.theme.compile', function ($app) {
            return new Command\Compile();
        });
        $this->commands([
            'command.theme.deploy', 'command.theme.clear', 
            'command.theme.preprocessor', 'command.theme.compile'
        ]);

        $this->app->singleton('theme.deployer', function ($app) {
            return new Deployer(
                $app['files'], 
                $app['theme.asset.resolver'], 
                $app['config'],
                $app['path.resources']
            );
        });

        $this->app->singleton('theme.complier', function ($app) {
            return new Complier(
                $app['files'], 
                $app['config'],
                $app['path.resources'],
                $app['path.public']
            );
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
            return [
                'command.theme.deploy', 'command.theme.clear', 
                'command.theme.preprocessor', 'command.theme.compile'
            ];
        }
    }
}
