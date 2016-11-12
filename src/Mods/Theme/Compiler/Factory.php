<?php

namespace  Mods\Theme\Compiler;

use Illuminate\Contracts\Pipeline\Pipeline;

class Factory
{
    /**
     * @var Illuminate\Contracts\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * Holds the array of compiler
     *
     * @var array
     */
    protected $compilers;

    public function __construct(Pipeline $pipeline, array $compilers)
    {
        $this->pipeline = $pipeline;
        $this->compilers =  $compilers;
    }

    public function handle($area, $theme, $assets, $console)
    {
        $manifest = [];
        foreach ($assets as $type => $asset) {
            $traveler = compact('manifest', 'asset', 'console', 'area', 'theme');
            $console->info("Processing compilation for $area => $theme => $type.");
            $manifest = $this->pipeline->send($traveler)
                ->through((isset($this->compilers[$type]))?$this->compilers[$type]:[])
                ->then(function ($traveler) use ($area, $theme, $type) {
                    $traveler['console']->info("Compilation for $area => $theme => $type done.");
                    $traveler['console']->line("==============================================");
                    return $traveler['manifest'];
                });
        }

        return $manifest;
    }
}
