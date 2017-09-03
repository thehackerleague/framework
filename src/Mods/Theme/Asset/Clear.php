<?php

namespace  Mods\Theme\Asset;

use Mods\Theme\AssetResolver;
use Mods\Theme\ThemeResolver;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Clear extends Console
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

    public function clear(
        $areas, $theme = null, $module = null,
        $type = null, $clearPublic = false
    ) {
        foreach ($areas as $area) {
            $this->info("Preparing {$area} section.");
            if (!$theme && !$module) {
                $this->info("\t* Clearing asset for {$area} section.");
                $this->files->cleanDirectory(
                    formPath([
                        $this->basePath, 'assets', $area
                    ])
                );
                if ($clearPublic) {
                    $this->files->cleanDirectory(
                        formPath([
                            $this->publicPath, 'assets', $area
                        ])
                    );
                }
                continue;
            }

            $areaHints = $this->assetResolver->getHints($area, $module);
            $areaPaths = $this->assetResolver->getPaths($area, $theme);

            foreach ($areaPaths as $themekey => $locations) {
                if (!$module && $theme) {
                    $this->clearPathAsset($area, $themekey, $type, $clearPublic);
                    continue;
                }

                foreach ($areaHints as $namespace => $location) {
                    $this->clearHintAsset($namespace, $area, $themekey, $type, $clearPublic);
                }
            }
        }
        $this->line("==============================================");
    }

    protected function clearHintAsset($namespace, $area, $theme, $inputType, $clearPublic)
    {
        $this->info("\t* Clearing files from `{$area}` ==> `{$theme}` ==> `{$namespace}`  module.");
        $assetType = $this->config->get('theme.asset', []);
        if ($inputType) {
            $assetType = array_intersect($assetType, $inputType);
        }
        foreach ($assetType as $type) {
            $this->info("\t\t* Clearing `{$type}`.");
            $this->files->cleanDirectory(formPath([
                $this->basePath, 'assets', $area, $theme, $type, $namespace
            ]));
            if ($clearPublic) {
                $this->files->cleanDirectory(formPath([
                    $this->publicPath, 'assets', $area, $theme, $type, $namespace
                ]));
            }
        }
    }

    protected function clearPathAsset($area, $theme, $inputType, $clearPublic)
    {
        $this->info("\t* Clearing files from `{$area}` ==> `{$theme}` location.");
        $basePath = [
            $this->basePath, 'assets', $area, $theme
        ];
        $publicPath = [
            $this->publicPath, 'assets', $area, $theme
        ];
        if ($inputType) {

            foreach ($inputType as $type) {
               $basePath[] = $type;
               $publicPath[] = $type;
               $this->info("\t\t* Clearing `{$type}`.");
               $this->cleanDirectoryFor($clearPublic, $basePath, $publicPath);
            }
            
        }

        $this->cleanDirectoryFor($clearPublic, $basePath, $publicPath);
        
    }

    private function cleanDirectoryFor($clearPublic, $basePath, $publicPath) {
        $this->files->cleanDirectory(formPath($basePath));
        if ($clearPublic) {
            $this->files->cleanDirectory(formPath($publicPath));
        }
    }
}
