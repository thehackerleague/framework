<?php

namespace Mods\Foundation;

use Mods\Theme\Theme;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\FixedArraySort as SortModules;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class ModuleRepository
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new service repository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(ApplicationContract $app, Filesystem $files, $manifestPath)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register the application modules.
     *
     * @param  array  $modules
     * @return void
     */
    public function load(array $modules, array $paths)
    {
        $manifest = $this->loadManifest();
        $modules = collect($modules)->filter(function ($value, $key) {
            return $value;
        })->keys()->all();

        $modules = array_combine($modules, $modules);

        if ($this->shouldRecompile($manifest, $modules)) {
            $manifest = $this->compileManifest($modules, $paths);
        }

        try {
            $autoload = $this->app['autoloader'];
        } catch (\Exception $e) {
            $autoload = require $this->app->bootstrapPath().'/autoload.php';
        }
        $this->registerAutoload($autoload, $manifest['autoload']);

        $config = $this->app['config'];
        $config->set('module.manifest', $manifest);

        foreach ($manifest['themes'] as $area => $themes) {
            $config->set('theme.'.$area.'.themes',
                collect($themes)->map(function ($item, $key) {
                        return new Theme($item);
                })->toArray()
            );
        }
        $config->set('app.providers', $manifest['providers']);
        $config->set('app.aliases', $manifest['aliases']);
    }

    /**
     * Compile the application manifest file.
     *
     * @param  array  $modules
     * @return array
     */
    protected function compileManifest($activeModules, $paths)
    {
        // The service manifest should contain a list of all of the modules for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
        $manifest = $this->freshManifest($activeModules);
        $moduleSorter =  new SortModules();
        $moduleSorter->add('mod_foundation', []);
        foreach (Finder::create()->files()->name('register.php')->in($paths) as $file) {
            $modules = $this->files->getRequire($file->getRealPath());
            foreach ($modules as $key => $module) {
                if ($module['type'] == 'module') {
                    if ($key != 'mod_foundation' && isset($activeModules[$key])) {
                        $moduleSorter->add($key, $module['depends']);
                    }
                    $manifest['modules'][$key] = $module;
                } else {
                    $manifest['themes'][$module['area']][$key] = $module;
                }
            }
        }

        try {
            $manifest['relsoved'] = $moduleSorter->sort();
        } catch (ElementNotFoundException $e) {
            echo($e->getMessage());
            exit(0);
        }
        $notFoundModule = array_diff($activeModules, $manifest['relsoved']);        
        if(count($notFoundModule)) {
            $word = (count($notFoundModule) > 1)?'Modules':'Module';
            echo(implode(', ', $notFoundModule). " $word not Found");
            exit(0); 
        }

        foreach ($manifest['relsoved'] as $code) {
            $manifest['providers'] = array_merge(
                $manifest['providers'],
                $manifest['modules'][$code]['providers']
            );
            $manifest['aliases'] = array_merge(
                $manifest['aliases'],
                $manifest['modules'][$code]['aliases']
            );
            if (isset($manifest['modules'][$code]['autoload'])) {
                $manifest['autoload'] = array_merge_recursive(
                    $manifest['autoload'],
                    $manifest['modules'][$code]['autoload']
                );
            }
        }

        return $this->writeManifest($manifest);
    }

    protected function registerAutoload($loader, $autoload)
    {
        $map = $autoload['psr-0'];
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = $autoload['psr-4'];
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = $autoload['classmap'];
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        $files = $autoload['files'];
        if ($files) {
            foreach ($files as $file) {
                $fileIdentifier = md5($file);
                $this->composerRequireFile($fileIdentifier, $file);
            }
        }
    }

    /**
     * Determine if the manifest should be compiled.
     *
     * @param  array  $manifest
     * @param  array  $modules
     * @return bool
     */
    public function shouldRecompile($manifest, $modules)
    {
        return is_null($manifest) || $manifest['active'] != $modules;
    }

    /**
     * Load the service provider manifest JSON file.
     *
     * @return array|null
     */
    public function loadManifest()
    {
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);

            if ($manifest) {
                return $manifest;
            }
        }
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     */
    public function writeManifest($manifest)
    {
        if ($this->app['config']->get('app.env')  == 'production') {
            $this->files->put(
                $this->manifestPath, '<?php return '.var_export($manifest, true).';'
            );
        }
        return $manifest;
    }

    /**
     * Create a fresh service manifest data structure.
     *
     * @param  array  $modules
     * @return array
     */
    protected function freshManifest(array $modules)
    {
        return [
            'active' => $modules,
            'relsoved' => [] ,
            'modules' => [],
            'themes' => [],
            'providers' => [],
            'aliases' => [],
            'autoload' => [
                'psr-4' => [],
                'psr-0' => [],
                'files' => [],
                'classmap' => [],
            ],
        ];
    }

    protected function composerRequireFile($fileIdentifier, $file)
    {
        if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
            require $file;

            $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
        }
    }
}
