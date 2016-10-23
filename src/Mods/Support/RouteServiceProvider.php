<?php

namespace Mods\Support;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as IlluminateServiceProvider;

abstract class RouteServiceProvider extends IlluminateServiceProvider
{
    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        $this->mapWebRoutes($router);
        $this->mapApiRoutes($router);
        if (config('module.modules.mod_backend', false)) {
            $this->mapBackendWebRoutes($router);
        }
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    private function mapWebRoutes(Router $router)
    {
        $router->group([
            'middleware' => ['web'],
        ], function ($router) {
            $this->registerWebRoutes($router);
        });
    }

     /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    private function mapApiRoutes(Router $router)
    {
        $router->group([
            'middleware' => 'api',
            'prefix' => 'api',
        ], function ($router) {
            $this->registerApiRoutes($router);
        });
    }

    /**
     * Define the "backend web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    private function mapBackendWebRoutes(Router $router)
    {
        $router->group([
            'middleware' => config('backend.middleware', ['web']),
            'prefix' => config('backend.prefix', 'admin'),
            'as' => 'backend.'
        ], function ($router) {
            $this->registerBackendRoutes($router);
        });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerWebRoutes(Router $router)
    {
    }


    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerApiRoutes(Router $router)
    {
    }

    /**
     * Define the "backend web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerBackendRoutes(Router $router)
    {
    }
}
