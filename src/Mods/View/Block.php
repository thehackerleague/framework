<?php

namespace Mods\View;

use Layout\Core\Contracts\Profiler;
use Illuminate\View\ViewFinderInterface;
use Layout\Core\Contracts\ConfigResolver;
use Illuminate\Contracts\Container\Container;
use Layout\Core\Contracts\Cacheable as Cache;
use Layout\Core\Block\AbstractBlock as BaseBlock;
use Layout\Core\Contracts\EventsDispatcher as Dispatcher;

abstract class Block extends BaseBlock
{
    /**
     * Container Instance
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    public $app;

    /**
     * Create a new view factory instance.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \Layout\Core\Contracts\Cacheable $cache
     * @param \Layout\Core\Contracts\ConfigResolver $config
     * @param \Layout\Core\Contracts\EventsDispatcher $events
     * @param \Layout\Core\Contracts\Profiler $profiler
     */
    public function __construct(
        Container $app,
        Cache $cache,
        ConfigResolver $config,
        Dispatcher $events,
        Profiler $profiler
    ) {

       $this->app = $app;
       parent::__construct($cache, $config, $events, $profiler);
    }

    /**
     * Get relevant path to template.
     *
     * @return string
     */
    public function getTemplate()
    {
        if(is_null($this->template)) {
            return null;
        }

        $section = $this->config->get('handle_layout_section');

        $template = explode(ViewFinderInterface::HINT_PATH_DELIMITER, $this->template);

        if (count($template) == 2) {
            $template = "{$template[0]}_$section::{$template[1]}";
        } else {
            $template = "$section::{$template[0]}";
        }
        
        return $template;
    }

    protected function getView($fileName, $viewVars)
    {
        return app('view')->make($fileName, $viewVars)->render();
    }
}
