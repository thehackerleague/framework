<?php

namespace Thl\Backend;

use Thl\Foundation\Contracts\AreaResolver;
use Illuminate\Contracts\Foundation\Application;

class Area implements AreaResolver
{
    /**
     * @{inherite}
     */
    public function owns(Application $app)
    {
        // We will not be able to detect the area until the router is matched.
        // So we add a Illuminate\Routing\Events\RouteMatched listener and
        // when the router is matched we set the area for the application
        // until then the area will be 'frontend'

        $app['events']->listen('Illuminate\Routing\Events\RouteMatched', function ($event) use ($app) {
            if ($app['router']->is('backend.*')) {
                $app['area'] = 'backend';
            }
        });
        return false;
    }
}
