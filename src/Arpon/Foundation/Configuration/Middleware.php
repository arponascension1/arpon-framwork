<?php

namespace Arpon\Foundation\Configuration;

class Middleware
{
    public array $globalMiddleware = [];
    public array $routeMiddleware = [];
    public array $middlewareGroups = []; // New property

    public function use(array $middleware): void
    {
        $this->globalMiddleware = $middleware;
    }

    public function route(array $middleware): void
    {
        $this->routeMiddleware = $middleware;
    }

    // New method to define middleware groups
    public function group(array $groups): void
    {
        $this->middlewareGroups = $groups;
    }
}
