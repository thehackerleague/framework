<?php

namespace Mods\View\Layout;

use Debugbar;
use Layout\Core\Contracts\Profiler as ProfilerContract;

class Profiler implements ProfilerContract
{
    /**
     * {@inheritDoc}
     */
    public function start($key)
    {
        if (config('debugbar.enabled', false)) {
            Debugbar::startMeasure($key);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function stop($key)
    {
        if (config('debugbar.enabled', false)) {
            Debugbar::stopMeasure($key);
        }
    }
}
