<?php

namespace  Mods\Theme;

use Mods\Theme\ThemeResolver;
use Mods\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use MJS\TopSort\ElementNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use MJS\TopSort\Implementations\FixedArraySort as SortAssets;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class PreProcess
{
    /**
     * The view finder implementation.
     *
     * @var \Mods\Theme\ThemeResolver
     */
    protected $themeResolver;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $application;

    /**
     * The view factory.
     *
     * @var \Mods\View\Factory
     */
    protected $viewFactory;

    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;


    protected $console;

    protected $basePath;
    /**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Mods\View\Factory $viewFactory
     * @param  \Mods\Theme\ThemeResolver $themeResolver
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        Application $app,
        Filesystem $files,
        ViewFactory $viewFactory,
        ThemeResolver $themeResolver,
        ConfigContract $config
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->application = $app;
        $this->viewFactory = $viewFactory;
        $this->themeResolver = $themeResolver;
        $this->basePath = $this->application['path.resources'];
    }

    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

    public function process($area = null, $theme = null, $module = null)
    {
        $manifest = [];

        if ($area) {
            $areas = [$area];
        } else {
            $areas = array_merge(['frontend'], array_values($this->config->get('app.areas', [])));
        }
        
        $oldArea = $this->application['area'];
        $oldLayoutXmlLocation = $this->config->get('layout.xml_location');

        $page = $this->viewFactory->getLayoutFactory();
        $pageUpdates = $page->getLayout()->getUpdate();

        $handles = $pageUpdates->collectHandlesFromUpdates();
        foreach ($areas as $area) {
            $manifest[$area] = [];
            $this->application['area'] = $area;
            $currentTheme = $this->themeResolver->getActive($area);
            $themes = $this->themeResolver->themeCollection($area);
            foreach ($themes as $key => $theme) {
                $manifest[$area][$key] = [];
                $this->mockApplicationTheme($area, $key, $currentTheme);
                $currentTheme = $key;
                foreach ($handles as $handle) {
                    if ($handle == 'default') {
                        continue;
                    }
                    $page->resetPage()
                        ->addHandle('default')
                        ->addHandle($handle)
                        ->buildLayout();
                    $manifest[$area][$key] = $this->prepareAsset(
                        $page->getLayout()->generateHeadElemets(),
                        $handle,
                        $manifest[$area][$key]
                    );
                }
            }
        }

        $this->writeConfig(['areas' => $manifest]);

        $this->application['area'] = $oldArea;
        $this->config->set('layout.xml_location', $oldLayoutXmlLocation);
    }

    protected function mockApplicationTheme($area, $themeKey, $currentTheme)
    {
        $locations = $this->config->get('layout.xml_location.'.$area);
        $currentPaths = $this->themeResolver->getPaths($area, $currentTheme)->toArray();

        foreach ($currentPaths as $path) {
            unset($locations[md5($path.'/layouts/')]);
        }
        $paths = $this->themeResolver->getPaths($area, $themeKey)->toArray();
        foreach ($paths as $path) {
            if (is_dir($appPath = $path.'/layouts/')) {
                $locations[md5($appPath)] = $appPath;
            }
        }
        $this->config->set('layout.xml_location.'.$area, $locations);
    }

    protected function prepareAsset($assets, $handle, $manifest)
    {
        $scripts = $assets['js'];
        $scripts =  '<?xml version="1.0"?>'
            . '<scripts xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . $scripts
            . '</scripts>';
        $xml = simplexml_load_string($scripts);
        $assetSorter =  new SortAssets();
        foreach ($xml->script as $script) {
            $attributes = $script->attributes();
            $assetSorter->add(
                (string) $attributes->src,
                ($attributes->depends)?(string) $attributes->depends:null
            );
        }

        return array_merge_recursive($manifest, ['js' => [$handle =>$assetSorter->sort()]]);
    }

    protected function writeConfig($manifest)
    {
        $manifest['assets'] = $this->config->get('theme.asset', []);
        $manifest['path'] = [
            'resources' => $this->basePath,
            'public' => $this->application['path.public'],
        ];
        $configPath = $this->getPath(
            [$this->basePath, 'assets', 'config.json']
        );
        $this->files->put(
            $configPath, json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }
    
    protected function getPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
