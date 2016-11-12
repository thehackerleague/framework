<?php

namespace  Mods\Theme;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;

class AssetResolver
{
    /**
     * Hint path delimiter value.
     *
     * @var string
     */
    const HINT_PATH_DELIMITER = '::';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The default paths for the loader.
     *
     * @var array
     */
    protected $paths = [];

     /**
     * The array of asset that have been located.
     *
     * @var array
     */
    protected $assets = [];

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $hints = [];

     /**
     * Create a new Asset reslover instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(
        Filesystem $files
    ) {
        $this->files = $files;
    }

    /**
     * Get the fully qualified location of the asset.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name, $area = 'frontend')
    {
        if (isset($this->assets[$area][$name])) {
            return $this->assets[$area][$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->assets[$area][$name] = $this->findNamedPathAsset($name, $area);
        }

        return $this->assets[$area][$name] = $this->findInPaths($name, $this->paths[$area]);
    }

    /**
     * Get the path to a asset with a named path.
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamedPathAsset($name, $area)
    {
        list($namespace, $view) = $this->getNamespaceSegments($name);

        return $this->findInPaths($view, $this->hints[$area][$namespace]);
    }

     /**
     * Find the given asset in the list of paths.
     *
     * @param  string  $name
     * @param  array   $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            if ($this->files->exists($assetPath = $path.'/'.$name)) {
                return $assetPath;
            }
        }

        throw new InvalidArgumentException("Asset [$name] not found.");
    }

    /**
     * Get the segments of a asset with a named path.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getNamespaceSegments($name)
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) != 2) {
            throw new InvalidArgumentException("Asset [$name] has an invalid name.");
        }

        if (! isset($this->hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Add a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints, $area = 'frontend')
    {
        /*$hints = (array) $hints;

        if (isset($this->hints[$area][$namespace])) {
            $hints = array_merge($this->hints[$area][$namespace], $hints);
        }*/

        $this->hints[$area][$namespace] = $hints;
    }

    /**
     * The paths for the loader.
     *
     * @param array paths
     */
    public function addLocation($paths)
    {
        $this->paths = array_merge($this->paths, (array) $paths);
    }

    /**
     * Returns whether or not the assesr specify a hint information.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasHintInformation($name)
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    /**
     * Get the active view paths.
     *
     * @return array
     */
    public function getPaths($area = null, $theme = null)
    {
        if ($area && isset($this->paths[$area])) {
            if ($theme) {
                if (isset($this->paths[$area][$theme])) {
                    return [$theme => $this->paths[$area][$theme]];
                } else {
                    return [];
                }
            }
            return $this->paths[$area];
        }
        return [];
    }

    /**
     * Get the namespace to file path hints.
     *
     * @return array
     */
    public function getHints($area = null, $namespace = null)
    {
        if ($area && isset($this->hints[$area])) {
            if ($namespace) {
                if (isset($this->hints[$area][$namespace])) {
                    return [$namespace => $this->hints[$area][$namespace]];
                } else {
                    return [];
                }
            }
            return $this->hints[$area];
        }
        return [];
    }
}
