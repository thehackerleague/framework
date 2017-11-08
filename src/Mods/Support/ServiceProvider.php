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
    protected function loadAssetsFrom($path, $namespace, $area = null)
    {
        $this->app['theme']->addAssetNamespace($namespace, $path, $area);
    }


    /**
     * Merge the given configuration recursivly with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeRecursiveConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge_recursive(require $path, $config));
    }

    /**
     * Register a advice for before.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    protected function registerBeforeAdvice($id, $target, $macro, $sortOrder = 10)
    {
        $this->app['aspect']->before($id, $target, $macro, $sortOrder);
    }

    /**
     * Register a advice for around.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    protected function registerAroundAdvice($id, $target, $macro, $sortOrder = 10)
    {
        $this->app['aspect']->around($id, $target, $macro, $sortOrder);
    }

    /**
     * Register a advice for after.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    protected function registerAfterAdvice($id, $target, $macro, $sortOrder = 10)
    {
        $this->app['aspect']->after($id, $target, $macro, $sortOrder);
    }
}
