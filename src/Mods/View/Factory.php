<?php

namespace Mods\View;

use Layout\Core\PageFactory;
use Mods\Theme\Factory as ThemeFactory;

class Factory
{
    /**
     * @var \Layout\Core\PageFactory $pageFactory
     */
    protected $pageFactory;

    /**
     * @var \Mods\Theme\Factory $themeFactory
     */
    protected $themeFactory;

    /**
     *
     * @param  \Layout\Core\PageFactory  $pageFactory
     * @param  \Mods\Theme\Factory $themeFactory
     */
    public function __construct(PageFactory $pageFactory, ThemeFactory $themeFactory)
    {
        $this->pageFactory = $pageFactory;
        $this->themeFactory = $themeFactory;
    }

    /**
     * Render the current page and return view
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $html = $this->pageFactory->render();
        $html['head'] = $this->updateAssetUrls($html['head']);
        return view('root', $html);
    }

    /**
     * Get the page factory
     *
     * @return  \Layout\Core\Factory
     */
    public function getPageFactory()
    {
        return $this->pageFactory;
    }

    /**
     * Fix the base url for the assets and do the last mintue updates
     *
     * @return  array
     */
    protected function updateAssetUrls($head)
    {
        $area = app()->area();
        $theme = $this->themeFactory->getActiveTheme($area);
        $manifest = $this->fetchManifest($area, $theme);
        $routeHandler = $this->pageFactory->routeHandler();

        if (isset($manifest['webpack']) && $manifest['webpack']) {
            $head['js'] = '<script src="'.$this->getAssetBaseUrl($area."/".$theme).'vendor.js"></script>'."\n".
                          '<script src="'.$this->getAssetBaseUrl($area."/".$theme).$routeHandler.'.js"></script>';
                          
            $head['css'] = '<link href="'.$this->getAssetBaseUrl($area."/".$theme).$routeHandler.'.css" media="all" rel="stylesheet" />';
        } elseif (isset($manifest['bundled']) && $manifest['bundled']) {
            $name = $this->getAssetBaseUrl('bundle/').md5($area.$theme.$routeHandler);
            $head['js'] = '<script src="'.$name.'.js"></script>';
            $head['css'] = '<link href="'.$name.'.css" media="all" rel="stylesheet" />';
        } else {
            $minified = (isset($manifest['minified']) && $manifest['minified']);
            $head['js'] = str_replace(
                ['%baseurl', '.js'], [$this->getJsBaseUrl($area, $theme), ($minified)?'.min.js':'.js'],
                $head['js']
            );
            $head['css'] = str_replace(
                ['%baseurl', '.css'], [$this->getCssBaseUrl($area, $theme), ($minified)?'.min.css':'.css'],
                 $head['css']
            );
        }
        return $head;
    }

    /**
     * Get the base url for asset
     *
     * @param string $path
     * @return string
     */
    public function getAssetBaseUrl($path)
    {
        return asset("assets/$path").'/';
    }

    /**
     * Get the base url for script
     *
     * @param string $area
     * @param string $theme
     * @return string
     */
    public function getJsBaseUrl($area, $theme)
    {
        return asset("assets/{$area}/{$theme}/js").'/';
    }

    /**
     * Get the base url for style
     *
     * @param string $area
     * @param string $theme
     * @return string
     */
    public function getCssBaseUrl($area, $theme)
    {
        return asset("assets/{$area}/{$theme}/css").'/';
    }

    /**
     * Get the base url for style
     *
     * @param string $area
     * @param string $theme
     * @return array
     */
    protected function fetchManifest($area, $theme)
    {
        $manifestPath = $this->getPath(
            [app('path.resources'), 'assets', $area, $theme, 'manifest.json']
        );
        if (!file_exists($manifestPath)) {
            return [];
        }
        return json_decode(file_get_contents($manifestPath), true);
    }

    protected function getPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
