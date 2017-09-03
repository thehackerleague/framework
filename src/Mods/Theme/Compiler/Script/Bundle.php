<?php

namespace  Mods\Theme\Compiler\Script;

use Mods\Theme\Compiler\Base\Bundle as BaseBundle;

class Bundle extends BaseBundle
{
    /**
     * Hold the type of asset need to be moved.
     *
     * @var string
     */
    protected $type = 'js';


    /**
     * Parse the given string of asset to get the asset links
     *
     * @param string $contents
     * @return array
     */
    protected function parseContents($contents) {
    	
    	if(preg_match_all('/src=["\']([^"\']+)["\']/i', $contents, $links, PREG_PATTERN_ORDER)) {
    		return array_map(function($link) {
    			return str_replace('%baseurl', '', $link);
    		}, $links[1]);
    	}
    	return [];
    }
}
