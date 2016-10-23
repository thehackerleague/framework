<?php

namespace Mods\View;

use Mods\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerLayoutBinder();
    }

    public function registerLayoutBinder()
    {
        $this->app->bind('\Layout\Core\Contracts\Cacheable', function ($app) {
            return $app['layout.cache'];
        });
        $this->app->bind('\Layout\Core\Contracts\EventsDispatcher', function ($app) {
            return $app['layout.event'];
        });
        $this->app->bind('\Layout\Core\Contracts\ConfigResolver', function ($app) {
            return $app['layout.config'];
        });
        $this->app->bind('\Layout\Core\Contracts\Profiler', function ($app) {
            return $app['layout.profile'];
        });

        $this->app->singleton('layout.cache', function ($app) {
            return new Layout\Cache($app['cache']);
        });

        $this->app->bind('layout.event', function ($app) {
            return new Layout\Event($app['events']);
        });

        $this->app->bind('layout.config', function ($app) {
            return new Layout\Config($app['config']);
        });

        $this->app->bind('layout.profile', function ($app) {
            return new Layout\Profiler();
        });
    }
}
