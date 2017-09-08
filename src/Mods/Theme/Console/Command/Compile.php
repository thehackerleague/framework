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
        {--s|simple : Use simple compilation.}
        {--type= : Compile only the given types.}
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
    public function handle()
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
        $complier = $app['theme.complier']->setConsole($this);
        $preprocessor = $app['theme.preprocessor']->setConsole($this);

        if (!$this->option('only')) {
            $clear->clear($areas, $theme, $module, $type, true);
            $deployer->deploy($areas, $theme, $module, $type);
            
            if (!$this->option('simple')) {
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
