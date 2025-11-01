<?php

// src/Arpon/View/ViewServiceProvider.php

namespace Arpon\View;

use Arpon\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('view', function ($app) {
            $files = $app['files']; // Assuming 'files' is bound to Filesystem
            $viewPath = $app->basePath() . '/resources/views';
            $coreViewPath = __DIR__;
            $factory = new Factory($files, [$viewPath, $coreViewPath]);

            return $factory;
        });
    }
}