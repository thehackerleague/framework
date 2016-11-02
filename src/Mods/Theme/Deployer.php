<?php

namespace  Mods\Theme;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Deployer
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


    protected $console;

    protected $basePath;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Mods\Theme\AssetResolver $assetResolver
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        Filesystem $files,
        AssetResolver $assetResolver,
        ConfigContract $config
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->assetResolver = $assetResolver;
        $this->basePath = app('path.resources');
    }

    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

    public function deploy($area = null, $theme = null, $module = null)
    {
        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        }
        foreach ($areas as $area) {
            $this->info("\n");
            $this->line("Deloying asset for {$area} section");
            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);
            foreach ($areaPaths as $themekey => $locations) {
                $this->info("\n");
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

            $this->info("\n");
            $this->line("Deloyed asset for {$area} section");

            $this->combineModuleAssests($area, $areaPaths);
        }
    }

    public function clear($area = null, $theme = null, $module = null)
    {
        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        }
        foreach ($areas as $area) {
            $this->line("Clearing asset for {$area} section");

            if (!$theme && !$module) {
                $assetType = $this->config->get('theme.asset', []);
                foreach ($assetType as $type => $resourcePath) {
                    $this->files->cleanDirectory($this->getPath([$this->basePath,$resourcePath,$area]));
                }
                continue;
            }

            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);
            foreach ($areaPaths as $themekey => $locations) {
                if (!$module && $theme) {
                    $this->line("Clearing asset for {$themekey} theme.");
                    foreach ($locations as $location) {
                        $this->clearPathAsset($location, $area, $themekey);
                    }
                    continue;
                }

                $this->line("Clearing asset for {$themekey} theme in {$area} section");
                foreach ($areaHints as $namespace => $location) {
                    $this->clearHintAsset($namespace, $location, $area, $themekey);
                }

                if ($module && !$theme) {
                    continue;
                }

                foreach ($locations as $location) {
                    $this->clearPathAsset($location, $area, $themekey);
                }
            }

            $this->line("Cleared asset for {$area} section");
        }
    }

    protected function combineModuleAssests($area, $areaPaths)
    {
        foreach ($areaPaths as $themekey => $locations) {
            foreach (['sass' => 'scss','less' => 'less'] as $lang => $ext) {
               $themePath = $this->getPath(
                    [$this->basePath, 'assets', $area, $themekey, $lang]
                );
               if($this->files->exists($themePath)) {
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
        $this->info("\n");
        $this->line("Deploying files from `{$namespace}` module.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                $this->getPath([$location,$type]),
                $this->getPath([$this->basePath,$resourcePath,$area,$theme,$type,$namespace])
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` files not found.");
            }
        }
    }

    protected function clearHintAsset($namespace, $location, $area, $theme)
    {
        $this->line("Clearing files from `{$namespace}` module.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            $this->info("Cleaing `{$type}`.");
            $this->files->cleanDirectory($this->getPath([
                $this->basePath,$resourcePath,$area,$theme,$type,$namespace
            ]));
        }
    }

    protected function movePathAsset($location, $area, $theme)
    {
        $this->info("\n");
        $this->line("Deploying files from `{$location}` location.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                $this->getPath([$location,$type]),
                $this->getPath([$this->basePath,$resourcePath,$area,$theme,$type])
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` not found.");
            }
        }
    }

    protected function cleanPathAsset($location, $area, $theme)
    {
        $this->line("Clearing files from `{$location}` location.");
        $assetType = $this->config->get('theme.asset', []);
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            $this->info("Cleaing `{$type}`.");
            $this->files->cleanDirectory($this->getPath([
                $this->basePath,$resourcePath,$area,$theme,$type
            ]));
        }
    }

    protected function writeThemeFiles($content, $name, $path)
    {
        $this->files->put(
            $path.'/'.$name, implode(PHP_EOL, $content)
        );
    }

    protected function getPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }

    protected function info($msg)
    {
        if ($this->console) {
            $this->console->info($msg);
        }
    }

    protected function warn($msg)
    {
        if ($this->console) {
            $this->console->warn($msg);
        }
    }

    protected function line($msg)
    {
        if ($this->console) {
            $this->console->line($msg);
        }
    }
}
