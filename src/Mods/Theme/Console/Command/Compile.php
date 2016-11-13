<?php

namespace Mods\Theme\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Compile extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'theme:compile
        {--m|minify : Minify the assets.}
        {--b|bundle : Bundle the assets.}
        {--o|only : Run only compilation.}
        {--p|skipPreProcess : Skip pre process.}
        {--type= : Compile only the given type.}
        {--area= : The area to be compile.}
    	{--theme= : The theme to be compile.}
    	{--module= : The module to be compile for the theme or area.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile the Theme & Module asset and ship it.';

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
        $type = null;
        if ($this->hasOption('type')) {
            $type = $this->option('type');
        }

        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));
        }
        $this->line("==============================================");
        
        $deployer = $app['theme.deployer']->setConsole($this);
        $complier = $app['theme.complier']->setConsole($this);
        $preprocessor = $app['theme.preprocessor']->setConsole($this);

        if (!$this->option('only')) {
            $deployer->clear($areas, $theme, $module, $type);
            $deployer->deploy($areas, $theme, $module, $type);

            if (!$this->option('skipPreProcess')) {
                $preprocessor->process($areas, $theme);
            }
        }

        $complier->compile($areas, $theme, $module, $type);
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
