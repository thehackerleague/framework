<?php

namespace Mods\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Mods\Foundation\Aspect\AdviceManager;
use Mods\Foundation\Aspect\Command\Inspect;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;

class AspectServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('aspect', function ($app) {
            return new AdviceManager();
        });

        $this->app->singleton('command.appect.inspect', function ($app) {   
            return new Inspect();;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                'command.appect.inspect'
            ]);
        } else {
            $this->app->booting(function ($app) {
                $this->controlTarget();
            });
        }
    }

    protected function controlTarget()
    {
        $contianer = $this->app;
        $aspect = $this->app['aspect'];

        $advices = collect($aspect->all());

        $advices->keys()->each(function ($target) use ($contianer, $aspect) {
            if($contianer->has($target) && $aspect->isProcessed($target)) { 
                $contianer->bind($target, $target.'\\Proxy');
            }
        });
    }
}