<?php

namespace  Thl\Theme;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class ThemeResolver
{
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
     * Create a new theme reslover instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        Filesystem $files,
        ConfigContract $config
    ) {
        $this->files = $files;
        $this->config = $config;
    }

    public function getActive($type = 'frontend')
    {
        return $this->config->get("theme.{$type}.active");
    }

    public function getPaths($type = 'frontend')
    {
        $paths = $this->themeCollection($type)
            ->map(function ($theme, $key) {
                return $theme->getPath();
            });
        return $paths;
    }

    protected function themeCollection($type = 'frontend')
    {
        $themeCollection = Collection::make();
        if ($active = $this->getActive($type)) {
            $active = $this->getTheme($active);
            $themeCollection->put(null, $active);
            while ($parent = $this->getParent($active)) {
                $parent = $this->getTheme($parent);
                $themeCollection->put(null, $parent);
                $active = $parent;
            }
        }
        return $themeCollection;
    }

    public function getTheme($key)
    {
        $theme = $this->config->get(
            'theme.themes.'.$key,
            false
        );
        if (!$theme) {
            throw new InvalidArgumentException("Theme {$this->getActive()} not found.");
        }

        return $theme;
    }

    protected function getParent($theme)
    {
        return $theme->getParent();
    }
}
