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

        foreach ($asset as $handle => $contents) {
            foreach ($contents as $key => $content) {
                $base = [
                    'assets', $area, $theme,
                    $this->getType(), str_replace('%baseurl', '', $content)
                ];
                $destination = $this->getPath([
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

                $origin = $this->getPath(array_merge([$this->container['path.resources']], $base));
                $minifedContent = $this->minify($this->files->get($origin));

                if ($this->files->put(
                    $destination,
                    $minifedContent,
                    true
                )) {
                    $console->info("\t* Minifing `{$this->getPath($base)}` in {$area} ==> {$theme}.", OutputInterface::VERBOSITY_DEBUG);
                } else {
                    $console->warn("`{$this->getPath($base)}` file not found in {$area} ==> {$theme}.");
                }

                $asset[$handle][$key] = str_replace('%baseurl', '%baseurlmin/', $content);
            }
        }

        $console->info("\t* Minification for {$this->getType()} in {$area} ==> {$theme} done.");

        $traveler['asset'] = $asset;
        $traveler['manifest']['minified'] = true;
        return $pass($traveler);
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

    protected function getPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
