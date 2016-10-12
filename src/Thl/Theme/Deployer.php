<?php

namespace  Thl\Theme;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
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
     * @var \Thl\Theme\AssetResolver
     */
    protected $assetResolver;

    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;


    protected $console;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Thl\Theme\AssetResolver $assetResolver
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
    }

    public function deploy()
    {
        $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        $hints = $this->assetResolver->getHints();
        $paths = $this->assetResolver->getPaths();
        foreach ($areas as $area) {
            $this->info("\n");
            $this->info("Deloying asset for {$area} section");
            if (isset($hints[$area])) {
                $areaHints = $hints[$area];
                foreach ($areaHints as $namespace => $location) {
                    $this->moveHintAsset($namespace, $location, $area);
                }
            }
            if (isset($paths[$area])) {
                $areaPaths = $paths[$area];
                foreach ($areaPaths as $location) {
                    $this->movePathAsset($location, $area);
                }
            }
            $this->info("\n");
            $this->info("Deloyed asset for {$area} section");
        }
    }

    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

    protected function moveHintAsset($namespace, $location, $area)
    {
        $this->info("\n");
        $this->info("Deploying files from `{$namespace}` module.");
        $assetType = $this->config->get('theme.asset', []);
        $basePath = app('path.resources');
        $DS = DIRECTORY_SEPARATOR;
        foreach ($assetType as $type => $resourcePath) {
            if ($this->files->copyDirectory(
                $location.$DS.$type,
                $basePath.$DS.$resourcePath.$DS.$area.$DS.$namespace
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` files not found.");
            }
        }
    }

    protected function movePathAsset($location, $area)
    {
        $this->info("\n");
        $this->info("Deploying files from `{$location}` location.");
        $assetType = $this->config->get('theme.asset', []);
        $basePath = app('path.resources');
        $DS = DIRECTORY_SEPARATOR;
        foreach ($assetType as $type => $resourcePath) {
            if ($this->files->copyDirectory(
                $location.$DS.$type,
                $basePath.$DS.$resourcePath.$DS.$area.$DS
            )) {
                $this->info("Moving `{$type}`.");
            } else {
                $this->warn("`{$type}` not found.");
            }
        }
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
}
