<?php

namespace Mods\Theme;

use Closure;
use Countable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as FactoryContract;

class Factory
{
    /**
     * The view finder implementation.
     *
     * @var \Mods\Theme\ThemeResolver
     */
    protected $themeResolver;

    /**
     * The view finder implementation.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    protected static $layoutXmlLocation = [];

    protected static $assetLocation = [];

    /**
     * Create a new view factory instance.
     *
     * @param  \Mods\Theme\ThemeResolver $themeResolver
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(
        ThemeResolver $themeResolver,
        FactoryContract $view,
        Dispatcher $events
    ) {
        $this->themeResolver = $themeResolver;
        $this->view = $view;
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a new asset namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addAssetNamespace($namespace, $hints, $area)
    {
        if ($this->container['config']->get('deploying', false)) {
            $this->container['theme.asset.resolver']->addNamespace($namespace, $hints, $area);
        }
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addViewNamespace($namespace, $hints)
    {
        $areas = array_merge(['frontend'], array_values($this->container['config']->get('app.areas', [])));
        $xmlLocations = [];
        foreach ($areas as $area) {
            $areaNamespace = $namespace.'_'.$area;
            if (is_dir($appPath = $this->container->resourcePath().'/views/processed/'.$area.'/'.$namespace)) {
                $this->view->addNamespace($areaNamespace, $appPath);
            }
            $paths = $this->themeResolver->getPaths($area)->toArray();
            foreach ($paths as $path) {
                if (is_dir($appPath = $path.'/templates/'.$namespace)) {
                    $this->view->addNamespace($areaNamespace, $appPath);
                }
                if (is_dir($appPath = $path.'/layouts/')) {
                    static::$layoutXmlLocation[$area][md5($appPath)] = $appPath;
                }
            }
            foreach ((array) $hints as $path) {
                if (is_dir($appPath = $path.'/'.$area.'/templates/')) {
                    $this->view->addNamespace($areaNamespace, $appPath);
                }
                if (is_dir($appPath = $path.'/'.$area.'/layouts/')) {
                    static::$layoutXmlLocation[$area][md5($appPath)] = $appPath;
                }
            }
        }
    }

    public function booted($app)
    {
        $this->registerLayoutXml($app);
        $this->registerLang($app);
        if ($app['config']->get('deploying', false)) {
            $this->registerAsset($app);
        }
    }

    protected function registerLayoutXml($app)
    {
        $oldXmlLocations = $app['config']->get('layout.xml_location', []);
        $xmlLocations = array_merge_recursive($oldXmlLocations, static::$layoutXmlLocation);
        $app['config']->set('layout.xml_location', $xmlLocations);
    }

    protected function registerLang($app)
    {
        $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));
        foreach ($areas as $area) {
            $paths = $this->themeResolver->getPaths($area)->toArray();
            foreach ($paths as $path) {
                if (is_dir($appPath = $path.'/lang/')) {
                    $app['translation.loader']->addLoaderPaths([$appPath]);
                }
            }
        }
    }

    protected function registerAsset($app)
    {
        $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));
        $assetPaths = [];
        foreach ($areas as $area) {
            $themes = $this->themeResolver->themeCollection($area);
            foreach ($themes as $key => $theme) {
                $paths = $this->themeResolver->getPaths($area, $key)->toArray();
                foreach ($paths as $path) {
                    if (is_dir($appPath = $path.'/assets/')) {
                        $assetPaths[$area][$key][] = $appPath;
                    }
                } 
            }
        }
        $app['theme.asset.resolver']->addLocation($assetPaths);
    }

    public function getActiveTheme($area)
    {
        return $this->themeResolver->getActive($area);
    }
}
