<?php

namespace  Mods\Theme;

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

    public function getActive($area = 'frontend')
    {
        return $this->config->get("theme.{$area}.active");
    }

    public function getPaths($area = 'frontend', $theme = null)
    {
        $paths = $this->activeThemeCollection($area, $theme)
            ->map(function ($theme, $key) {
                return $theme->getPath();
            });
        return $paths;
    }

    public function activeThemeCollection($area = 'frontend', $theme = null)
    {
        if (!$theme) {
            $theme = $this->getActive($area);
        }
        $themeCollection = Collection::make();
        $active = $this->getTheme($theme, $area);
        $themeCollection->put(null, $active);
        while ($parent = $this->getParent($active)) {
            $parent = $this->getTheme($parent, $area);
            $themeCollection->put(null, $parent);
            $active = $parent;
        }
        return $themeCollection;
    }

    public function themeCollection($area = 'frontend')
    {
        $themes = $this->config->get(
            "theme.{$area}.themes",
            []
        );
        $themeCollection = Collection::make($themes);
        return $themeCollection;
    }

    public function getTheme($key, $area)
    {
        $theme = $this->config->get(
            "theme.{$area}.themes.{$key}",
            false
        );
        if (!$theme) {
            throw new InvalidArgumentException("Theme {$key} not found.");
        }

        return $theme;
    }

    protected function getParent($theme)
    {
        return $theme->getParent();
    }
}
