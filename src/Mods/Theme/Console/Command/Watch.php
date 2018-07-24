<?php

namespace Mods\Theme\Console\Command;

use Illuminate\Console\Command;
use Symfony\Component\Process\ { Process, InputStream };
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;

class Watch extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'theme:watch
         {--area= : The area to be watched.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'watch for changes to be compiled.';

    protected $watcher;

    protected $watcherInput;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $app = $this->getFreshAsset();

        $area = $this->option('area');

        if ($area == null) {
            $areas = array_merge(['frontend'], array_values($app['config']->get('app.areas', [])));        
            $area = $this->choice('Choose a Area?', $areas, 0);
        }

        $theme = $app['config']->get("theme.{$area}.active");

        $this->callCompiler($area, $theme);
        $this->watch($app, $area, $theme);      
        
    }

    public function watch($app, $area, $theme)
    {
        $assetResolver = $app['theme.asset.resolver'];

        $this->watcherInput = new InputStream();

        $this->watcher = new Process('node build/theme/watcher.js');
        $this->watcher->setInput($this->watcherInput);
        $this->watcher->setTimeout(3600);
        $this->watcher->start();       

        $areaHints = implode('|', $assetResolver->getHints($area));
        $areaPaths = implode('|', array_flatten($assetResolver->getPaths($area, $theme)));


        $this->watcherInput->write($areaHints.'|'.$areaPaths. PHP_EOL);

        try {       

            $this->watcher->wait(function ($type, $buffer) use($area, $theme) {
                if (Process::ERR === $type) {
                    $this->error('ERR > '.$buffer);
                } else {
                    $this->line('NODE > '.$buffer);
                    if(trim($buffer) == '__CHNAGED__') {
                        $this->callCompiler($area, $theme);
                    }
                }
            });
        } catch (RuntimeException $e) {            
            $this->watch($app, $area, $theme);
        }   
    }

    public function callCompiler($area, $theme)
    {
        $this->info('Compiling assets.');
        $this->callSilent('theme:webpack', [
            '--area' => $area,
            '--theme' => $theme, 
        ]);
        $this->callWebapck($area, $theme);
    }

    public function callWebapck($area, $theme)
    {
        $process = new Process("yarn run dev --area=$area --theme=$theme");
        $process->run();
        if ($process->isSuccessful()) {
            $this->info('Webpack build successfull');
        }
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