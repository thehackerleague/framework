<?php

namespace  Mods\Theme\Compiler\Style;

use Mods\Theme\Compiler\Base\Minifier as BaseMinifier;

class Minifier extends BaseMinifier
{
    /**
     * Hold the type of asset need to be moved.
     *
     * @var string
     */
    protected $type = 'css';

    /**
     * Check if the asset can be minified
     *
     * @return true|string
     */
    protected function canMinify()
    {
        if (!class_exists('CSSmin')) {
            return "`CSSmin` is not found \n composer require tubalmartin/cssmin";
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
        $compressor = new \CSSmin();
        return $compressor->run($content);
    }

    /**
     * Parse the given string of asset to get the asset links
     *
     * @param string $contents
     * @return array
     */
    protected function parseContents($contents) {

        if(preg_match_all('/href=["\']([^"\']+)["\']/i', $contents, $links, PREG_PATTERN_ORDER)) {
            return array_map(function($link) {
                return str_replace('%baseurl', '', $link);
            }, $links[1]);
        }
        return [];
    }
}
