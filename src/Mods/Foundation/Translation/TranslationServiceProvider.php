<?php

namespace Mods\Foundation\Translation;

use Illuminate\Translation\TranslationServiceProvider as BaseProvider;

class TranslationServiceProvider extends BaseProvider
{
    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader($app['files'], $app['path.lang']);
        });
    }
}
