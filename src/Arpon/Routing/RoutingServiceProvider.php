<?php

// src/Arpon/Routing/RoutingServiceProvider.php

namespace Arpon\Routing;

use Arpon\Support\ServiceProvider;
use Arpon\Http\Request;
use Arpon\Routing\Redirector;
use Arpon\Routing\UrlGenerator;

class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        

        $this->app->singleton('request', function ($app) {
            return Request::capture();
        });

        $this->app->singleton('redirect', function ($app) {
            $redirector = new Redirector($app->make('url'));

            // If the session is set on the application, we will inject the session
            // instance onto the redirector instance. This allows the redirector
            // to flash error bag instances into the session for convenience.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });

        $this->app->singleton('url', function ($app) {
            $router = $app->make('router');
            return new UrlGenerator($router->getRoutes(), $app->make('request'));
        });
    }


}