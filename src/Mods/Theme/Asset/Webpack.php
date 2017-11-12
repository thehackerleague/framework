<?php

namespace  Mods\Theme\Asset;

use Mods\Theme\AssetResolver;
use Mods\Theme\ThemeResolver;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Config\Repository as ConfigContract;

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
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Filesystem\Filesystem  $files     
     * @param  \Mods\Theme\AssetResolver $assetResolver
     * @param  \Illuminate\Contracts\Config\Repository $config
     * @param  string $basePath
     * @param  string $publicPath
     * @return void
     */
    public function __construct(
        Application $app,
        Filesystem $files,
        AssetResolver $assetResolver,
        ConfigContract $config,
        $basePath,
        $publicPath
    ) {
        $this->app = $app;
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
                            //if(!isset($assets[$type]) || !is_array($assets[$type])) { continue; }
                            $mutatedValue = array_map(function ($url) use ($area, $key, $type) {
                                $fullPath = formPath(['themePath', $type, $url], '/');
                                return "require('$fullPath')";
                            }, $this->getAssetPath($type, $assets[$type][$handle]));
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
                            $this->console->info("\t* Genrated `Webpack` Entry point for `{$handle}` in {$area} ==> {$key}.");
                        } else {
                            $this->console->warn("Unable to create Webpack  for `{$handle}` in {$area} ==> {$theme}.");
                        }
                    }
                }
            }
        } catch (FileNotFoundException $e) {
            Log::debug($e);
            $this->console->error("Unexpectedly something went wrong during deployment.");
        }
    }

    protected function getAssetPath($type, $assets)
    {
        if(in_array($type, ['css', 'js'])) {
            $callFn = 'parse'.ucfirst($type).'Contents';
            return $this->$callFn($assets);
        }

        $langParser = $this->config->get('theme.webpack.additional.'.$type, false);

        if($langParser !== false) {
            return $this->app->make($langParser)->handle($assets);
        }

        return [];

    }

    /**
     * Parse the given string of asset to get the asset links
     *
     * @param string $contents
     * @return array
     */
    protected function parseCssContents($contents) 
    {
        if(preg_match_all('/href=["\']([^"\']+)["\']/i', $contents, $links, PREG_PATTERN_ORDER)) {
            return array_filter(array_map(function($link) {
                return str_replace(['%baseurl', 'theme.css'], '', $link);
            }, $links[1]));
        }
        return [];
    }

     /**
     * Parse the given string of asset to get the asset links
     *
     * @param string $contents
     * @return array
     */
    protected function parseJsContents($contents) 
    {
        
        if(preg_match_all('/src=["\']([^"\']+)["\']/i', $contents, $links, PREG_PATTERN_ORDER)) {
            return array_map(function($link) {
                return str_replace('%baseurl', '', $link);
            }, $links[1]);
        }
        return [];
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
