<?php

namespace Mods\Foundation\Bootstrap;

use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

class DetectArea
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $areas = collect($app['config']->get('app.areas', []));
        $currentArea = $areas->first(function ($area, $detector) use ($app) {
            if (app($detector)->owns($app)) {
                return true;
            }
            return false;
        });
        if (!$currentArea) {
            $currentArea = 'frontend';
            if ($app->runningInConsole()) {
                $currentArea = 'console';
            }
        }
        $app['area'] = $currentArea;
    }
}
