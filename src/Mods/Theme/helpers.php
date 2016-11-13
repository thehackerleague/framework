<?php

if (! function_exists('formPath')) {
    function formPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
