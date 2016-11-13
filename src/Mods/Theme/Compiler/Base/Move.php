<?php

namespace  Mods\Theme\Compiler\Base;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Move
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Hold the type of asset need to be moved.
     *
     * @var string
     */
    protected $type = null;

    /**
     * Create a new compiler command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  string $basePath
     * @return void
     */
    public function __construct(Filesystem $files, Container $container)
    {
        $this->files = $files;
        $this->container = $container;
    }

    public function handle($traveler, $pass)
    {
        extract($traveler);

        $base = ['assets', $area, $theme, $this->getType()];

        if ($this->files->copyDirectory(
            formPath(array_merge([$this->container['path.resources']], $base)),
            formPath(array_merge([$this->container['path.public']], $base))
        )) {
            $console->info("\t* Publishing `{$this->getType()}` in {$area} ==> {$theme}.");
        } else {
            $console->warn("`{$this->getType()}` files not found in {$area} ==> {$theme}.");
        }

        /*
        foreach ($asset as $handle => $contents) {
            foreach ($contents as $content) {
                $base = ['assets', $area, $theme, $this->getType(), str_replace('%baseurl','',$content)];
                $destination = formPath(array_merge([$this->container['path.public']], $base));
                $destination = $this->files->dirname($destination);

                if(!$this->files->isDirectory($destination)) {
                    $this->files->makeDirectory($destination, 0777, true);
                }

                if ($this->files->copy(
                    formPath(array_merge([$this->container['path.resources']], $base)),
                    formPath(array_merge([$this->container['path.public']], $base))
                )) {
                    $console->info("Publishing `".formPath($base)."` in {$area} ==> {$theme}.", OutputInterface::VERBOSITY_DEBUG);
                } else {
                    $console->warn("`".formPath($base)."` file not found in {$area} ==> {$theme}.");
                }
            }
        }
        */
        return $pass($traveler);
    }

    /**
     * Get the type of asset need to be processed.
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function getType()
    {
        if ($this->type == null) {
            throw new \InvalidArgumentException('No Asset type given');
        }
        return $this->type;
    }
}
