<?php

namespace Mods\Theme\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Deploy extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'theme:deploy
        {--area= : The area to be deploy.}
    	{--theme= : The theme to be deploy.}
    	{--module= : The module to be deploy for the theme or area.}
        {--type= : Compile only the given type.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the asset.';

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
        
        $app['theme.clear']->setConsole($this)->clear($areas, $theme, $module, $type);
        $app['theme.deployer']->setConsole($this)->deploy($areas, $theme, $module, $type);

        $this->info("\n");
        $this->info("Deployed successfully!");
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
