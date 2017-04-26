<?php

namespace Mods\Theme\Contracts;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Container\Container;

interface Compiler
{
	/**
     * Create a new compiler command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Filesystem $files, Container $container);

    /**
     * Compiles the given type and passes the control to next compiler.
     *
     * @param  Array $traveler
     * @param  \Clouser $pass
     * @return mixed
     */
    public function handle($traveler, $pass);
}