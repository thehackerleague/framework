<?php

namespace Mods\Foundation\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface AreaResolver
{
    /**
     * Check if the area is owned by the section.
     *
     * @param Illuminate\Contracts\Foundation\Application $app
     * @return bool
     */
    public function owns(Application $app);
}
