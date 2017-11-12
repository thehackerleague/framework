<?php

namespace  Mods\Theme\Asset;

use Mods\Theme\AssetResolver;
use Mods\Theme\ThemeResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Events\Dispatcher;
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
     * The view finder implementation.
     *
     * @var \Mods\Theme\ThemeResolver
     */
    protected $themeResolver;

    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

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
     * @param  \Mods\Theme\ThemeResolver $themeResolver
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string $basePath
     * @return void
     */
    public function __construct(
        Filesystem $files,
        AssetResolver $assetResolver,
        ThemeResolver $themeResolver,
        ConfigContract $config,
        Dispatcher $events,
        $basePath
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->assetResolver = $assetResolver;
        $this->themeResolver = $themeResolver;
        $this->events = $events;
        $this->basePath = $basePath;
    }

    public function deploy($areas, $theme = null, $module = null, $type = null)
    {
        $basePath = $this->basePath;
        foreach ($areas as $area) {
            $this->info("Deloying asset for {$area} section");
            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);

            foreach ($areaPaths as $themekey => $locations) {
                $this->info("  => Deloying asset for {$themekey} theme in {$area} section");
                foreach ($areaHints as $namespace => $location) {
                    $this->moveHintAsset($namespace, $location, $area, $themekey, $type);
                }

                if ($module && !$theme) {
                    continue;
                }

                foreach ($locations as $location) {
                    $this->movePathAsset($location, $area, $themekey, $type);
                }
            }


            $response = $this->events->fire('theme.asset.deploy.after', compact('area', 'areaPaths', 'basePath', 'type'));

            $this->table(['Asset Deploy After Event'], $response);
            

            $this->info("Deloyed asset for {$area} section");
            $this->line("==============================================");
            
        }
        $this->writeConfig();
    }

    protected function writeConfig()
    {
        $manifest = [];
        $themeConfig = [];
        $assets = $this->config->get('theme.asset', []);
        $modulePaths = $this->config->get('module.paths', []);

        $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        foreach ($areas as $area) {
            $themes = $this->themeResolver->themeCollection($area);
            foreach ($themes as $key => $theme) {
                $themeConfig[$area][$key] = [
                    'parent' => $theme->getParent()
                ];
                $manifest[$area][$key] = array_flip($assets);
            }
        }
        
        $configPath = formPath(
            [$this->basePath, 'assets', 'config.json']
        );
        $this->files->put(
            $configPath, json_encode([
                'assets' => $assets, 
                'areas' => $manifest, 
                'modulePaths' => $modulePaths,
                'themes' => $themeConfig
            ], JSON_PRETTY_PRINT)
        );
    }

    protected function moveHintAsset($namespace, $location, $area, $theme, $inputType)
    {
        $this->info("\t* Deploying files from `{$namespace}` module.");
        $assetType = $this->config->get('theme.asset', []);
        if ($inputType) {
            $assetType = array_intersect($assetType, $inputType);
        }
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                formPath([$location, $area, $type]),
                formPath([$this->basePath, $resourcePath, $area, $theme, $type, $namespace])
            )) {
                $this->info("\t\t* Moving `{$type}`.");
            } else {
                $this->warn("\t\t* `{$type}` not found.");
            }
        }
    }

    protected function movePathAsset($location, $area, $theme, $inputType)
    {
        $this->info("\t* Deploying files from `{$location}` location.");
        $assetType = $this->config->get('theme.asset', []);
        if ($inputType) {
            $assetType = array_intersect($assetType, $inputType);
        }
        $resourcePath = 'assets';
        foreach ($assetType as $type) {
            if ($this->files->copyDirectory(
                formPath([$location, $type]),
                formPath([$this->basePath, $resourcePath, $area, $theme, $type])
            )) {
                $this->info("\t\t* Moving `{$type}`.");
            } else {
                $this->warn("\t\t* `{$type}` not found.");
            }
        }
    }
}
