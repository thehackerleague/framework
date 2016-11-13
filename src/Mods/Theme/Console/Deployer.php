<?php

namespace  Mods\Theme\Console;

use Mods\Theme\AssetResolver;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Deployer extends Console
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The view finder implementation.
     *
     * @var \Mods\Theme\AssetResolver
     */
    protected $assetResolver;

    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Resource location
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Mods\Theme\AssetResolver $assetResolver
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  string $basePath
     * @return void
     */
    public function __construct(
        Filesystem $files,
        AssetResolver $assetResolver,
        ConfigContract $config,
        $basePath
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->assetResolver = $assetResolver;
        $this->basePath = $basePath;
    }

    public function deploy($areas, $theme = null, $module = null)
    {
        foreach ($areas as $area) {
            $this->info("\n");
            $this->line("Deloying asset for {$area} section");
            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);
            foreach ($areaPaths as $themekey => $locations) {
                $this->line("Deloying asset for {$themekey} theme in {$area} section");
                foreach ($areaHints as $namespace => $location) {
                    $this->moveHintAsset($namespace, $location, $area, $themekey);
                }

                if ($module && !$theme) {
                    continue;
                }

                foreach ($locations as $location) {
                    $this->movePathAsset($location, $area, $themekey);
                }
            }

            $this->line("Deloyed asset for {$area} section");

            $this->combineModuleAssests($area, $areaPaths);
        }
    }

    public function clear($areas, $theme = null, $module = null)
    {
        foreach ($areas as $area) {
            if (!$theme && !$module) {
                $this->info("Clearing asset for {$area} section.");
                $this->files->cleanDirectory(
                    formPath([
                        $this->basePath, 'assets', $area
                    ])
                );
                continue;
            }

            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);

            foreach ($areaPaths as $themekey => $locations) {
                if (!$module && $theme) {
                    $this->clearPathAsset($area, $themekey);
                    continue;
                }

                foreach ($areaHints as $namespace => $location) {
                    $this->clearHintAsset($namespace, $area, $themekey);
                }
            }
        }
    }

    protected function combineModuleAssests($area, $areaPaths)
    {
        foreach ($areaPaths as $themekey => $locations) {
            foreach (['sass' => 'scss', 'less' => 'less'] as $lang => $ext) {
                $themePath = formPath(
                    [$this->basePath, 'assets', $area, $themekey, $lang]
                );
                if ($this->files->exists($themePath)) {
                    $import = [];
                    foreach (Finder::create()->files()->name('_module.'.$ext)->in([$themePath]) as $file) {
                        $import[] = "@import '{$file->getRelativePathName()}' ";
                    }
                    $this->writeThemeFiles($import, 'theme.'.$ext, $themePath);
                }
            }
        }
    }

    protected function moveHintAsset($namespace, $location, $area, $theme)
    {
        $this->line("Deploying files from `{$namespace}` module.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                formPath([$location, $type]),
                formPath([$this->basePath, $resourcePath, $area, $theme, $type, $namespace])
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` files not found.");
            }
        }
    }

    protected function clearHintAsset($namespace, $area, $theme)
    {
        $this->info("Clearing files from `{$area}` ==> `{$theme}` ==> `{$namespace}`  module.");
        $assetType = $this->config->get('theme.asset', []);
        foreach ($assetType as $type) {
            $this->info("Cleaing `{$type}`.");
            $this->files->cleanDirectory(formPath([
                $this->basePath, 'assets', $area, $theme, $type, $namespace
            ]));
        }
    }

    protected function movePathAsset($location, $area, $theme)
    {
        $this->line("Deploying files from `{$location}` location.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                formPath([$location, $type]),
                formPath([$this->basePath, $resourcePath, $area, $theme, $type])
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` not found.");
            }
        }
    }

    protected function clearPathAsset($area, $theme)
    {
        $this->info("Clearing files from `{$area}` ==> `{$theme}` location.");
        $this->files->cleanDirectory(formPath([
            $this->basePath, 'assets', $area, $theme
        ]));
    }

    protected function writeThemeFiles($content, $name, $path)
    {
        $this->files->put(
            $path.'/'.$name, implode(PHP_EOL, $content)
        );
    }
}
