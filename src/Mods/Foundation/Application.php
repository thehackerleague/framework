<?php

namespace Mods\Foundation;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Mods\Foundation\ModuleRepository;
use Illuminate\Foundation\Application as BaseApplicaiton;

class Application extends BaseApplicaiton
{
    /**
     * The Laravel framework version.
     *
     * @var string
     */
    const VERSION = '0.0.0-dev';

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredModules()
    {
        $manifestPath = $this->getCachedModulePath();

        (new ModuleRepository($this, new Filesystem, $manifestPath))
                    ->load($this->config['module.modules'], $this->config['module.paths']);
    }

    /**
     * Get or check the current application area.
     *
     * @return string|bool
     */
    public function area()
    {
        if (func_num_args() > 0) {
            $patterns = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();

            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $this['area'])) {
                    return true;
                }
            }
            return false;
        }

        return $this['area'];
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedModulePath()
    {
        return $this->bootstrapPath().'/cache/modules.php';
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return '';
    }
}
