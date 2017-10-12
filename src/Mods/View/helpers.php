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
