<?php

namespace Mods\Support;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

abstract class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register a view file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadViewsFrom($path, $namespace)
    {
        $this->app['theme']->addViewNamespace($namespace, $path);
    }

    /**
     * Register a asset file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadAssetsFrom($path, $namespace, $area = 'frontend')
    {
        $this->app['theme']->addAssetNamespace($namespace, $path, $area);
    }
}
