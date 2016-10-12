<?php

namespace Thl\Theme\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ThemeDeployCommand extends Command
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
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the asset.';

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
        //$this->call('theme:clear');

        $app = $this->getFreshAsset();

        $theme = $app['theme.deployer']->setConsole($this)->deploy();

        $this->info("\n");
        $this->info("Theme {$theme} deployed successfully!");
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
