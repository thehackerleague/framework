<?php

namespace  Mods\Theme\Asset;

use Mods\Theme\AssetResolver;
use Mods\Theme\ThemeResolver;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Symfony\Component\Console\Output\OutputInterface;

class Webpack extends Console
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
     * Public location
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Mods\Theme\AssetResolver $assetResolver
     * @param  \Illuminate\Contracts\Config\Repository $config
     * @param  string $basePath
     * @param  string $publicPath
     * @return void
     */
    public function __construct(
        Filesystem $files,
        AssetResolver $assetResolver,
        ConfigContract $config,
        $basePath,
        $publicPath
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->assetResolver = $assetResolver;
        $this->basePath = $basePath;
        $this->publicPath = $publicPath;
    }

    public function compile($areas, $theme = null)
    {
        $metadata = json_decode($this->readConfig(), true);
        $areas = array_intersect_key($metadata['areas'], array_flip($areas));
        $handles = $metadata['handles'];
        $aAssets = $metadata['assets'];
        try {
            foreach ($areas as $area => $themes) {
                if ($theme) {
                    $themes = array_intersect_key($themes, [$theme => 1]);
                }
                foreach ($themes as $key => $assets) {
                    foreach ($handles[$area] as $handle) {
                        $collection = [];
                        foreach ($aAssets as $type) {
                            $basePath = formPath([$this->basePath, 'assets', $area, $key, $type]);
                            $mutatedValue = array_map(function ($val) use ($basePath) {
                                $fullPath = formPath([$basePath, str_replace('%baseurl', '', $val)]);
                                return "require('$fullPath')";
                            }, $assets[$type][$handle]);
                            $collection = array_merge($collection, $mutatedValue);
                        }
                        $destination = formPath([$this->basePath, 'assets', $area, $key, 'webpack']);
                        if (!$this->files->isDirectory($destination)) {
                            $this->files->makeDirectory($destination, 0777, true);
                        }

                        $destination = formPath([$destination, $handle.'.js']);
                        $this->writeManifest(['webpack' => 1], $area, $key);
                        
                        if ($this->files->put(
                            $destination,
                            implode("\n", $collection)
                        )) {
                            $this->console->info("\t* Webpack File for `{$handle}` in {$area} ==> {$key}.");
                        } else {
                            $this->console->warn("Unable to create Webpack  for `{$handle}` in {$area} ==> {$theme}.");
                        }
                    }
                }
            }
        } catch (FileNotFoundException $e) {
            $this->console->error("Unexpectedly something went wrong during deployment.");
        }
    }

    protected function readConfig()
    {
        $configPath = formPath(
            [$this->basePath, 'assets', 'config.json']
        );
        return $this->files->get($configPath);
    }

    protected function writeManifest($manifest, $area, $theme)
    {
        $configPath = formPath(
            [$this->basePath, 'assets', $area, $theme, 'manifest.json']
        );
        $this->files->put(
            $configPath, json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }
}
