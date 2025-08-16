<?php

// src/Arpon/View/ViewServiceProvider.php

namespace Arpon\View;

use Arpon\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('view', function ($app) {
            $factory = new Factory($app);

            // Register the view finder with the factory
            $factory->getFinder()->addPath($app->basePath() . '/resources/views');

            return $factory;
        });
    }
}
