<?php

namespace  Mods\Theme\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Complier extends Console
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
     * Resource location
     *
     * @var string
     */
    protected $basePath;

    /**
     * public location
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  string $basePath
     * @param  string $publicPath
     * @return void
     */
    public function __construct(
        Filesystem $files,
        ConfigContract $config,
        $basePath,
        $publicPath
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->basePath = $basePath;
        $this->publicPath = $publicPath;
    }

    public function compile($area = null, $theme = null, $module = null)
    {
        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        }

        $metadata = json_decode($this->readConfig(), true);

        foreach ($metadata['areas'] as $area => $themes) {
            foreach ($themes as $theme => $assets) {
                $this->moveAssetToPublic($area, $theme, $assets);
            }
        }
    }

    public function moveAssetToPublic($area, $theme, $assets)
    {
        foreach ($assets as $type => $contents) {
            if ($this->files->copyDirectory(
                $this->getPath([$this->basePath, 'assets', $area, $theme, $type]),
                $this->getPath([$this->publicPath, 'assets', $area, $theme, $type])
            )) {
                $this->info("Publishing `{$type}` in {$area} ==> {$theme}.");
            } else {
                $this->warn("`{$type}` files not found in {$area} ==> {$theme}.");
            }
        }
    }

    protected function readConfig()
    {
        $configPath = $this->getPath(
            [$this->basePath, 'assets', 'config.json']
        );
        return $this->files->get($configPath);
    }
}