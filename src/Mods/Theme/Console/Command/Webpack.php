<?php

namespace Mods\Theme\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Webpack extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'theme:webpack
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
    protected $description = 'Prepare the assets for webpack.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $app = $this->getFreshAsset();
        
        $area = $this->option('area');
        $theme = $this->option('theme');
        $module = $this->option('module');
        $type = $this->option('type');
        
        if($type != null) {
            $type = explode(',', $type);        
        }

        if ($area != null) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));
        }
        $this->line("==============================================");
        
        $deployer = $app['theme.deployer']->setConsole($this);
        $clear = $app['theme.clear']->setConsole($this);
        $webpack = $app['theme.webpack']->setConsole($this);
        $complier = $app['theme.complier']->setConsole($this);
        $preprocessor = $app['theme.preprocessor']->setConsole($this);

        $clear->clear($areas, $theme, $module, $type, true);
        $deployer->deploy($areas, $theme, $module, $type);
        $preprocessor->process($areas, $theme);
        $complier->compile($areas, $theme, $module, 'img');
        $webpack->compile($areas, $theme);
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
