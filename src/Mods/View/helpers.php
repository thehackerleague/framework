<?php

if (! function_exists('render')) {
    
    function render()
    {
        return app('Mods\View\Factory')->render();
    }
}