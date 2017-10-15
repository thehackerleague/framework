<?php

namespace Mods\View;

use Mods\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $directives = require __DIR__.'/directives.php';

        collect($directives)->each(function ($item, $key) {
            Blade::directive($key, $item);
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLayoutBinder();
    }

    public function registerLayoutBinder()
    {
        $this->app->bind(\Layout\Core\Contracts\Cacheable::class, function ($app) {
            return $app['layout.cache'];
        });
        $this->app->bind(\Layout\Core\Contracts\EventsDispatcher::class, function ($app) {
            return $app['layout.event'];
        });
        $this->app->bind(\Layout\Core\Contracts\ConfigResolver::class, function ($app) {
            return $app['layout.config'];
        });
        $this->app->bind(\Layout\Core\Contracts\Profiler::class, function ($app) {
            return $app['layout.profile'];
        });

        $this->app->singleton('layout.cache', function ($app) {
            return new Layout\Cache($app['cache']);
        });

        $this->app->singleton('layout.event', function ($app) {
            return new Layout\Event($app['events']);
        });

        $this->app->singleton('layout.config', function ($app) {
            return new Layout\Config($app['config']);
        });

        $this->app->singleton('layout.profile', function ($app) {
            return new Layout\Profiler();
        });
    }
}
