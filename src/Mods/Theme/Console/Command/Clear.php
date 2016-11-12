<?php

namespace Mods\Theme\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Clear extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'theme:clear
        {--area= : The area to be clear.}
    	{--theme= : The theme to be clear.}
    	{--module= : The module to be clear for the theme or area.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the asset.';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $app = $this->getFreshAsset();
        $area = null;
        if ($this->hasOption('area')) {
            $area = $this->option('area');
        }
        $theme = null;
        if ($this->hasOption('theme')) {
            $theme = $this->option('theme');
        }
        $module = null;
        if ($this->hasOption('module')) {
            $module = $this->option('module');
        }

        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));
        }

        $app['theme.deployer']->setConsole($this)->clear($areas, $theme, $module);
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshAsset()
    {
        $app = require $this->laravel->bootstrapPath().'/app.php';

        $app['events']->listen(
            'bootstrapped: Illuminate\Foundation\Bootstrap\LoadConfiguration',
            function ($app) {
                $app['config']->set('deploying', true);
            }
        );
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app;
    }
}
