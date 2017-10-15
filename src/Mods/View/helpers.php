<?php


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
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    function render()
    {
        return app(Mods\View\Factory::class)->render();
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
