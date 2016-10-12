<?php

namespace Thl\View\Layout;

use Debugbar;
use Layout\Core\Contracts\Profiler as ProfilerContract;

class Profiler implements ProfilerContract
{
    public function start($key)
    {
        if (config('debugbar.enabled', false)) {
            Debugbar::startMeasure($key);
        }
    }

    public function stop($key)
    {
        if (config('debugbar.enabled', false)) {
            Debugbar::stopMeasure($key);
        }
    }
}
