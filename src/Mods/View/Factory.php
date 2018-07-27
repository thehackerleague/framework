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
     * @param string $handle
     * @return \Illuminate\View\View
     */
    public function render($handle)
    {
        $html = $this->pageFactory->render($handle);
        $html['head'] = $this->updateAssetUrls($html['head']);
        return view('root', $html);
    }

    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            view()->share($key);
        } else {
            view()->share($key,  $value);
        }

        return $this;
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
        $routeHandler = $this->pageFactory->currentHandle();

        if (isset($manifest['webpack']) && $manifest['webpack']) {
            $handleAsset = $manifest['compiledAsset'][$routeHandler];
            $js = $css = '';
            foreach ($handleAsset['js'] as $value) {
                $js .=  $this->getScriptTag($this->getAssetBaseUrl($area."/".$theme).$value);
            }

            $head['js'] = $js;

            foreach ($handleAsset['css'] as $value) {
                $css .=  $this->getStyleTag($this->getAssetBaseUrl($area."/".$theme).$value);
            }

            $head['css'] = $css;

        } elseif (isset($manifest['bundled']) && $manifest['bundled']) {
            $name = md5($area.$theme.$routeHandler);
            $head['js'] = $this->getScriptTag($this->getJsBaseUrl($area, $theme).$name.'.js');
            $head['css'] = $this->getStyleTag($this->getCssBaseUrl($area, $theme).$name.'.css');
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
     * Get the script tag
     *
     * @param string $url
     * @return string
     */
    protected function getScriptTag($url)
    {
        return '<script src="'.$name.'"></script>'."\n";
    }

    /**
     * Get the style tag
     *
     * @param string $url
     * @return string
     */
    protected function getStyleTag($url)
    {
        return '<link href="'.$url.'" media="all" rel="stylesheet" />'."\n";
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
