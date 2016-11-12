<?php

namespace  Mods\Theme\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Mods\Theme\Compiler\Factory;

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

    public function compile($areas, $theme = null, $module = null)
    {
        $metadata = json_decode($this->readConfig(), true);

        foreach ($metadata['areas'] as $area => $themes) {
            foreach ($themes as $theme => $assets) {
                $this->compiler->handle($area, $theme, $assets, $this->console);
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
