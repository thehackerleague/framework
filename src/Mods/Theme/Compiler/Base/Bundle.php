<?php

namespace  Mods\Theme\Compiler\Base;

use Mods\Theme\Contracts\Compiler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Bundle implements Compiler
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
        $traveler['manifest']['bundled'] = false;
        if (!$console->option('bundle') || $console->option('simple')) {
            return $pass($traveler);
        }
        $basePaths = [
            $this->container['path.public'], 'assets', $area,
            $theme, $this->getType()
        ];
        $destination = formPath($basePaths);
        if (!$this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        }
        foreach ($asset as $handle => $contents) {
            $filename = md5($area.$theme.$handle);
            $destination = formPath(array_merge($basePaths, ["$filename.{$this->getType()}"]));
            $this->files->put($destination, '');

            $contents = $this->parseContents($contents);

            foreach ($contents as $content) {

                if ($console->option('minify')) { 
                    $content = str_replace(
                        '.'.$this->getType(), '.min.'.$this->getType(),
                        $content
                    );
                }
                
                $origin = formPath([
                    $this->container['path.resources'], 'assets', $area,
                    $theme, $this->getType(),
                    $content
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
            $console->info("\t* Bundling `{$this->getType()}` for `{$handle}` in {$area} ==> {$theme} to public.");
        }
        
        $traveler['manifest']['bundled'] = true;
        return $pass($traveler);
    }

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
