<?php

namespace Mods\View;

use Layout\Core\PageFactory;
use Mods\Theme\Factory as ThemeFactory;

class Factory
{
    /**
     * @var \Layout\Core\Factory $pageFactory
     */
    protected $pageFactory;

    /**
     * @var \Mods\Theme\Factory $themeFactory
     */
    protected $themeFactory;

    /**
     *
     * @param  \Layout\Core\Factory  $pageFactory
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
        $head['js'] = str_replace('%baseurl', $this->getJsBaseUrl(), $head['js']);
        $head['css'] = str_replace('%baseurl', $this->getCssBaseUrl(), $head['css']);
        return $head;
    }

    /**
     * Get the base url for script
     * 
     * @return string
     */ 
    public function getJsBaseUrl()
    {
        $area = app()->area();
        $theme = $this->themeFactory->getActiveTheme($area);
        return asset("assets/{$area}/{$theme}/js").'/';
    }

    /**
     * Get the base url for style
     * 
     * @return string
     */ 
    public function getCssBaseUrl()
    {
        $area = app()->area();
        $theme = $this->themeFactory->getActiveTheme($area);
        return asset("assets/{$area}/{$theme}/css").'/';
    }
}
