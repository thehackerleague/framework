<?php

namespace Mods\Theme;

use Blade;
use Mods\Theme\Asset;
use Mods\Theme\Console\Command;
use Mods\Support\ServiceProvider;
use Mods\Theme\Compiler\Factory as ComplierFactory;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('asset_img', function ($expression) {
            $area = app()->area();
            $theme = app(\Mods\Theme\Factory::class)->getActiveTheme($area);
            return asset("assets/{$area}/{$theme}/img/{$expression}");
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerThemeDeployer();

        $this->app->register(
            \Mods\Theme\EventServiceProvider::class
        );

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
            return new Command\Deploy();
        });
        $this->app->singleton('command.theme.clear', function ($app) {
            return new Command\Clear();
        });
        $this->app->singleton('command.theme.preprocessor', function ($app) {
            return new Command\PreProcess();
        });
        $this->app->singleton('command.theme.compile', function ($app) {
            return new Command\Compile();
        });
        $this->app->singleton('command.theme.webpack', function ($app) {
            return new Command\Webpack();
        });
        $this->commands([
            'command.theme.deploy', 'command.theme.clear',
            'command.theme.preprocessor', 'command.theme.compile',
            'command.theme.webpack'
        ]);

        $this->app->singleton('theme.deployer', function ($app) {
            return new Asset\Deployer(
                $app['files'],
                $app['theme.asset.resolver'],
                $app['theme.resolver'],
                $app['config'],
                $app['events'],
                $app['path.resources']
            );
        });

        $this->app->singleton('theme.clear', function ($app) {
            return new Asset\Clear(
                $app['files'],
                $app['theme.asset.resolver'],
                $app['config'],
                $app['path.resources'],
                $app['path.public']
            );
        });

        $this->app->singleton('theme.complier', function ($app) {
            return new Asset\Complier(
                $app['files'],
                $app['theme.asset.complier'],
                $app['path.resources']
            );
        });

        $this->app->singleton('theme.asset.complier', function ($app) {
            return new ComplierFactory(
                $app['Illuminate\Pipeline\Pipeline'],
                $app['config']->get('theme.compliers', [])
            );
        });

        $this->app->singleton('theme.preprocessor', function ($app) {
            return new Asset\PreProcess(
                $app,
                $app['files'],
                $app['Mods\View\Factory'],
                $app['theme.resolver'],
                $app['config']
            );
        });

        $this->app->singleton('theme.webpack', function ($app) {
            return new Asset\Webpack(
                $app,
                $app['files'],
                $app['theme.asset.resolver'],
                $app['config'],
                $app['path.resources'],
                $app['path.public']
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
                'command.theme.preprocessor', 'command.theme.compile',
                'command.theme.webpack'
            ];
        }
    }
}
