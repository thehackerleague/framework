<?php

namespace  Mods\Theme\Compiler\Base;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Minifier
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
        $traveler['manifest']['minified'] = false;
        if (!$console->option('minify')) {
            return $pass($traveler);
        }
        $reason = $this->canMinify();
        if ($reason !== true) {
            $console->warn($reason);
            return $pass($traveler);
        }

        $destination = formPath([
            $this->container['path.resources'], 'assets', $area,
            $theme, $this->getType(), 'min'
        ]);

        if (!$this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        } else {
            $this->files->cleanDirectory($destination);
        }

        if ($console->option('simple')) {
            $this->simpleMinfier($area, $theme, $console);
        } else {
            $asset = $this->processedMinfier($asset, $area, $theme, $console);
        }

        $console->info("\t* Minification for {$this->getType()} in {$area} ==> {$theme} done.");

        $traveler['asset'] = $asset;
        $traveler['manifest']['minified'] = true;
        return $pass($traveler);
    }

    /**
     *
     *
     * @param string $area
     * @param string $theme
     * @param $console
     *
     * @return array
     */
    protected function simpleMinfier($area, $theme, $console)
    {
        $base = [
            $this->container['path.resources'], 'assets', $area,
            $theme, $this->getType()
        ];
        $originPath = formPath($base);

        $files = $this->files->allFiles($originPath);

        foreach ($files as $file) {
            $destination = formPath(array_merge($base, [
                'min',
                $file->getRelativePath()
            ]));
            $filename = $file->getFileName();

            if (!$this->files->isDirectory($destination)) {
                $this->files->makeDirectory($destination, 0777, true);
            }
            $destination .= '/'.$filename;

            $origin = $file->getPathName();
            $minifedContent = $this->minify($this->files->get($origin));

            if ($this->files->put(
                $destination,
                $minifedContent,
                true
            )) {
                $console->info("\t* Minifing `".$origin."` in {$area} ==> {$theme}.", OutputInterface::VERBOSITY_DEBUG);
            } else {
                $console->warn("`".$origin."` file not found in {$area} ==> {$theme}.");
            }
        }
    }
    
    /**
     *
     *
     * @param array $asset
     * @param string $area
     * @param string $theme
     * @param $console
     *
     * @return array
     */
    protected function processedMinfier($asset, $area, $theme, $console)
    {
        foreach ($asset as $handle => $contents) {
            foreach ($contents as $key => $content) {
                $base = [
                    'assets', $area, $theme,
                    $this->getType(), str_replace('%baseurl', '', $content)
                ];
                $destination = formPath([
                    $this->container['path.resources'], 'assets', $area,
                    $theme, $this->getType(), 'min',
                    str_replace('%baseurl', '', $content)
                ]);
                $filename = $this->files->basename($destination);
                $destination = $this->files->dirname($destination);

                if (!$this->files->isDirectory($destination)) {
                    $this->files->makeDirectory($destination, 0777, true);
                }
                $destination .= '/'.$filename;

                $origin = formPath(array_merge([$this->container['path.resources']], $base));
                $minifedContent = $this->minify($this->files->get($origin));

                if ($this->files->put(
                    $destination,
                    $minifedContent,
                    true
                )) {
                    $console->info("\t* Minifing `".formPath($base)."` in {$area} ==> {$theme}.", OutputInterface::VERBOSITY_DEBUG);
                } else {
                    $console->warn("`".formPath($base)."` file not found in {$area} ==> {$theme}.");
                }

                $asset[$handle][$key] = str_replace('%baseurl', '%baseurlmin/', $content);
            }
        }

        return $asset;
    }

    /**
     * Check if the asset can be minified
     *
     * @return true|string
     */
    abstract protected function canMinify();

    /**
     * Check if the asset can be minified
     *
     * @param string $content
     * @return string
     */
    abstract protected function minify($content);

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
