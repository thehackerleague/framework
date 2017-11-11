<?php

namespace Mods\Theme\Listeners;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class AssetEventSubscriber
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

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
     * @return void
     */
    public function __construct(
        Filesystem $files
    ) {
        $this->files = $files;
    }

    /**
     * Handle asset is deployed events.
     */
    public function onDeployAfter($area, $areaPaths, $basePath, $type) {

    	if(empty($type) || in_array('css', $type)) {
	    	//$this->combineFonts($area, $areaPaths, $basePath);
            $this->patchBaseUrl($area, $areaPaths, $basePath);
	    }

    	return [$this->response];
    }

    protected function patchBaseUrl($area, $areaPaths, $basePath)
    {
        foreach ($areaPaths as $themekey => $locations) {

            $themePath = formPath(
                [$basePath, 'assets', $area, $themekey, 'css']
            );

             $files = Finder::create()->files()
                        ->name('*.css')
                        ->contains('@{baseurl}')
                        ->in([$themePath]);   

            foreach ($files as $file) {
                $realPath = $file->getRealPath();
                $this->files->put($file->getRealPath(),str_replace('@{baseurl}',"/assets/$area/$themekey/", $file->getContents()));
            }

            $this->response .="  => Patching Base-Url...\n";
        }
    }

    protected function combineFonts($area, $areaPaths, $basePath)
    {
    	foreach ($areaPaths as $themekey => $locations) {

    		$fontPath = formPath(
                [$basePath, 'assets', $area, $themekey, 'fonts']
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