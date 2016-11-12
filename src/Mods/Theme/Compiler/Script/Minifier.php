<?php

namespace  Mods\Theme\Compiler\Script;

use Mods\Theme\Compiler\Base\Minifier as BaseMinifier;

class Minifier extends BaseMinifier
{
    /**
     * Hold the type of asset need to be moved.
     *
     * @var string
     */
    protected $type = 'js';

    /**
     * Check if the asset can be minified
     *
     * @return true|string
     */
    protected function canMinify()
    {
        if (!class_exists('JShrink\Minifier')) {
            return "`JShrink\Minifier` is not found \n composer require tedivm/jshrink";
        }
        return true;
    }

    /**
     * Check if the asset can be minified
     *
     * @param string $content
     * @return string
     */
    protected function minify($content)
    {
        return \JShrink\Minifier::minify($content);
    }
}
