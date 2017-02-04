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
        $type = null, $clearPublic = false, $clearBundled = false
    ) {
        if ($clearBundled) {
            $this->bundled();
        }
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

    protected function clearHintAsset($namespace, $area, $theme, $type, $clearPublic)
    {
        $this->info("\t* Clearing files from `{$area}` ==> `{$theme}` ==> `{$namespace}`  module.");
        $assetType = $this->config->get('theme.asset', []);
        if ($type) {
            $assetType = array_intersect($assetType, [$type]);
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

    protected function clearPathAsset($area, $theme, $type, $clearPublic)
    {
        $this->info("\t* Clearing files from `{$area}` ==> `{$theme}` location.");
        $basePath = [
            $this->basePath, 'assets', $area, $theme
        ];
        $publicPath = [
            $this->publicPath, 'assets', $area, $theme
        ];
        if ($type) {
            $basePath[] = $type;
            $publicPath[] = $type;
            $this->info("\t\t* Clearing `{$type}`.");
        }
        $this->files->cleanDirectory(formPath($basePath));
        if ($clearPublic) {
            $this->files->cleanDirectory(formPath($publicPath));
        }
    }

    public function bundled()
    {
        $this->info("Clearing bundled assets.");
        $this->files->cleanDirectory(formPath([
            $this->publicPath, 'assets', 'bundle'
        ]));
    }
}
