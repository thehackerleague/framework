<?php

namespace  Mods\Theme\Compiler\Base;

use Mods\Theme\Contracts\Compiler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Minifier implements Compiler
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
        $originPath = formPath([
            $this->container['path.resources'], 'assets', $area,
            $theme, $this->getType()
        ]);

        $files = $this->files->allFiles($originPath);

        foreach ($files as $file) {
            $destination = str_replace(
                '.'.$this->getType(), '.min.'.$this->getType(),
                $file->getPathName()
            );

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
            $contents = $this->parseContents($contents);
            foreach ($contents as $key => $content) {
                $origin = formPath([
                    $this->container['path.resources'], 'assets', $area, $theme,
                    $this->getType(), $content
                ]);

                $destination = str_replace(
                    '.'.$this->getType(), '.min.'.$this->getType(),
                    $origin
                );
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

                $asset[$handle][$key] = str_replace('.'.$this->getType(), '.min.'.$this->getType(), $content);
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
     * Parse the given string of asset to get the asset links
     *
     * @param string $contents
     * @return array
     */
    abstract protected function parseContents($contents);

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
