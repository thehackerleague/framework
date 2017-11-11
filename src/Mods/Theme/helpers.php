<?php

if (! function_exists('formPath')) {
    function formPath($paths, $separator = DIRECTORY_SEPARATOR)
    {
        return implode($separator, $paths);
    }
}
