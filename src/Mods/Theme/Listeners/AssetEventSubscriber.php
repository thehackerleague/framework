<?php

namespace Mods\Theme\Listeners;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Foundation\Application;

class AssetEventSubscriber
{

	/**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Resource location
     *
     * @var string
     */
    protected $basePath;

    /**
     * Event Response
     *
     * @var string
     */
    protected $response;

	/**
     * Create a new config cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct(
        Filesystem $files,
        ConfigContract $config,
        Application $app
    ) {
        $this->files = $files;
        $this->config = $config;
        $this->basePath = $app['path.resources'];
    }

    /**
     * Handle user asset is deployed events.
     */
    public function onDeployAfter($area, $areaPaths, $areaHints, $type) {

    	if(empty($type) || in_array('css', $type)) {
	    	$this->combineModuleAssests($area, $areaPaths);
	    	$this->combineFonts($area, $areaPaths, $areaHints);
	    }

    	return [$this->response];
    }

    protected function combineFonts($area, $areaPaths, $areaHints)
    {
    	foreach ($areaPaths as $themekey => $locations) {

    		$fontPath = formPath(
                [$this->basePath, 'assets', $area, $themekey, 'fonts']
            );

            if ($this->files->exists($fontPath)) {
            	$files = Finder::create()->directories()->in([$fontPath])->depth('< 1');
            	foreach ($files as $file) {
                    $realPath = $file->getRealPath();
                    if ($this->files->copyDirectory(
		               	$realPath,
		                $fontPath
		            )) {
                    	$this->files->deleteDirectory($realPath);
                    }
                }
            	$this->response .="  => Combining Fonts...\n";
            }
            
        }

    }

    protected function combineModuleAssests($area, $areaPaths)
    {
        foreach ($areaPaths as $themekey => $locations) {
            foreach (['scss' => 'scss', 'less' => 'less'] as $lang => $ext) {
                $themePath = formPath(
                    [$this->basePath, 'assets', $area, $themekey, $lang]
                );
                if ($this->files->exists($themePath)) {
                    $import = ['$base-url: '.'"/assets/'.$area.'/'.$themekey.'/";'];
                    $files = Finder::create()->files()
                        ->name('_theme.'.$ext)
                        ->name('_module.'.$ext)
                        ->in([$themePath]);   
                    foreach ($files as $file) {
                        $import[] = "@import \"{$file->getRelativePathName()}\";";
                    }
                    $this->response .="  => Combining `{$ext}` in `{$area}` area for `{$themekey}` theme.\n";
                    $this->writeThemeFiles($import, 'theme.'.$ext, $themePath);
                }
            }
        }
    }

    protected function writeThemeFiles($content, $name, $path)
    {
        $this->files->put(
            $path.'/'.$name, implode(PHP_EOL, $content)
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'theme.asset.deploy.after',
            'Mods\Theme\Listeners\AssetEventSubscriber@onDeployAfter'
        );
    }

}