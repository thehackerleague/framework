<?php

namespace  Mods\Theme\Asset;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Mods\Theme\Compiler\Factory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Complier extends Console
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The compiler instance.
     *
     * @var \Mods\Theme\Compiler\Factory
     */
    protected $compiler;

    /**
     * Resource location
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new compiler command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Mods\Theme\Compiler\Factory  $compiler
     * @param  string $basePath
     * @return void
     */
    public function __construct(
        Filesystem $files,
        Factory $compiler,
        $basePath
    ) {
        $this->files = $files;
        $this->compiler = $compiler;
        $this->basePath = $basePath;
    }

    public function compile($areas, $theme = null, $module = null, $type = null)
    {
        $metadata = json_decode($this->readConfig(), true);
        $areas = array_intersect_key($metadata['areas'], array_flip($areas));
        try {
            foreach ($areas as $area => $themes) {
                if ($theme) {
                    $themes = array_intersect_key($themes, [$theme => 1]);
                }
                foreach ($themes as $key => $assets) {
                    if ($type) {
                        $assets = array_intersect_key($assets, [$type => 1]);
                    }
                    $manifest = $this->compiler->handle($area, $key, $assets, $this->console);
                    $this->writeManifest($manifest, $area, $key);
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
