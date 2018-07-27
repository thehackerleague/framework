<?php

use Layout\Core\Exceptions\InvalidRouterHandleException;

if (! function_exists('layout')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Mods\View\Factory|\Mods\Contracts\View\Factory
     */
    function layout()
    {
        return app(Mods\View\Factory::class);
    }
}

if (! function_exists('render')) {
	/**
     * Render the layout for the current route.
     *
     * @param string $handle
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    function render($handle = null)
    {
        return layout()->render($handle?:getCurrentRoute());
    }
}


if (! function_exists('parseMultipleArgs')) {
    /**
     * Parse expression.
     *
     * @param  string $expression
     * @return \Illuminate\Support\Collection
     */
    function parseMultipleArgs($expression)
    {
        return collect(explode(',', $expression))->map(function ($item) {
            return trim($item);
        });
    }
}

if (! function_exists('stripQuotes')) {
    /**
     * Strip single quotes.
     *
     * @param  string $expression
     * @return string
     */
    function stripQuotes($expression)
    {
        return str_replace("'", '', $expression);
    }
}

if (! function_exists('getCurrentRoute')) {
    /**
     * Get the current router name in snake case.
     *
     * @throws InvalidRouterHandleException
     * @return string
     */
    function getCurrentRoute()
    {
        $routeName = \Route::currentRouteName();
        $routerHandler = str_replace('.', '_', strtolower($routeName));
        if (empty($routerHandler) || is_null($routerHandler)) {
            if (app('config')->get('layout.strict', false)) {
                throw new InvalidRouterHandleException('Invalid Router Handle supplied');
            }
        }
        return $routerHandler;
    }
}