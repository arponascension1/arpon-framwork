<?php

namespace Arpon\Foundation;

use Arpon\Contracts\Http\Kernel;
use Arpon\Foundation\Configuration\Middleware;
use Arpon\Foundation\Configuration\Exceptions;

class ApplicationBuilder
{
    private $app;
    private Middleware $middleware;

    public function __construct($app)
    {
        $this->app = $app;
        $this->middleware = new Middleware();
    }

    public function withRouting($web, $commands, $health): static
    {
        require $web;
        return $this;
    }

    public function withMiddleware($callback): static
    {
        $callback($this->middleware);
        return $this;
    }

    public function withExceptions($callback): static
    {
        $callback(new Exceptions);
        return $this;
    }

    public function create()
    {
        $this->app->registerCoreContainerAliases();
        $this->app->registerCoreBindings();
        $this->app->registerCoreAliases();
        $this->app->registerCoreProviders();

        $this->app->booted(function () {
            $kernel = $this->app->make(Kernel::class);
            $kernel->setMiddleware($this->middleware->globalMiddleware);
            $kernel->setRouteMiddleware($this->middleware->routeMiddleware);
            $kernel->setMiddlewareGroups($this->middleware->middlewareGroups); // Pass middleware groups
        });

        return $this->app;
    }
}
