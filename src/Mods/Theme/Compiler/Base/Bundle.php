<?php

namespace  Mods\Theme\Compiler\Base;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Bundle
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
        $traveler['manifest']['bundled'] = false;
        if (!$console->option('bundle')) {
            return $pass($traveler);
        }
        $basePaths = [
            $this->container['path.resources'], 'assets', $area,
            $theme, $this->getType(), 'bundle'
        ];
        $destination = formPath($basePaths);

        if (!$this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        }
        foreach ($asset as $handle => $contents) {
            $destination = formPath(array_merge($basePaths, ["$handle.{$this->getType()}"]));
            $this->files->put($destination, '');
            foreach ($contents as $key => $content) {
                $origin = formPath([
                    $this->container['path.resources'], 'assets', $area,
                    $theme, $this->getType(),
                    str_replace('%baseurl', '', $content)
                ]);

                if ($this->files->append(
                    $destination,
                    $this->files->get($origin)
                )) {
                    $console->info("\t* Reading `$origin` for `{$handle}` in {$area} ==> {$theme}.", OutputInterface::VERBOSITY_DEBUG);
                } else {
                    $console->warn("`".formPath($base)."` file not found in {$area} ==> {$theme}.");
                }
            }
            $console->info("\t* Bundling `{$this->getType()}` for `{$handle}` in {$area} ==> {$theme}.");
        }
        
        $traveler['manifest']['bundled'] = true;
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
