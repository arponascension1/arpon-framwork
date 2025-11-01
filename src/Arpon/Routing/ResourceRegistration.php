<?php

namespace Arpon\Routing;

use Arpon\Container\Container;

class ResourceRegistration
{
    /**
     * The resource name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The resource controller.
     *
     * @var string
     */
    protected string $controller;

    /**
     * The resource routes.
     *
     * @var Route[]
     */
    protected array $routes = [];

    /**
     * The resource methods.
     *
     * @var array
     */
    protected array $methods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    /**
     * The options for the resource.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * The middleware for the resource.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * Create a new resource registration instance.
     *
     * @param string $name
     * @param string $controller
     * @param  array  $options
     * @return void
     */
    public function __construct(string $name, string $controller, array $options = [])
    {
        $this->name = $name;
        $this->controller = $controller;
        $this->options = $options;
    }

    /**
     * Add the resource routes.
     *
     * @param Router $router
     * @return void
     */
    protected function addResourceRoutes(Router $router): void
    {
        $resource = str_replace('/', '.', $this->name);

        $uriSegments = explode('/', $this->name);
        $lastSegment = end($uriSegments);
        $paramName = rtrim($lastSegment, 's');

        foreach ($this->getFilteredMethods() as $method) {
            $this->{'add' . ucfirst($method) . 'Route'}($router, $resource, $paramName);
        }
    }

    /**
     * Get the filtered list of methods for the resource.
     *
     * @return array
     */
    protected function getFilteredMethods(): array
    {
        if (isset($this->options['only'])) {
            return array_intersect($this->methods, (array) $this->options['only']);
        }

        if (isset($this->options['except'])) {
            return array_diff($this->methods, (array) $this->options['except']);
        }

        return $this->methods;
    }

    protected function addIndexRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->get($this->name, [$this->controller, 'index'])->name("{$resource}.index");
    }

    protected function addCreateRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->get("{$this->name}/create", [$this->controller, 'create'])->name("{$resource}.create");
    }

    protected function addStoreRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->post($this->name, [$this->controller, 'store'])->name("{$resource}.store");
    }

    protected function addShowRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->get("{$this->name}/{{$paramName}}", [$this->controller, 'show'])->name("{$resource}.show");
    }

    protected function addEditRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->get("{$this->name}/{{$paramName}}/edit", [$this->controller, 'edit'])->name("{$resource}.edit");
    }

    protected function addUpdateRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->put("{$this->name}/{{$paramName}}", [$this->controller, 'update'])->name("{$resource}.update");
        $this->routes[] = $router->patch("{$this->name}/{{$paramName}}", [$this->controller, 'update']);
    }

    protected function addDestroyRoute(Router $router, string $resource, string $paramName): void
    {
        $this->routes[] = $router->delete("{$this->name}/{{$paramName}}", [$this->controller, 'destroy'])->name("{$resource}.destroy");
    }

    public function except(array|string $methods): static
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();
        return $this;
    }

    public function only(array|string $methods): static
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();
        return $this;
    }

    public function middleware(array|string|null $middleware): static
    {
        $this->middleware = is_array($middleware) ? $middleware : func_get_args();
        return $this;
    }

    public function __destruct()
    {
        $router = Container::getInstance()->make('router');
        $this->addResourceRoutes($router);

        foreach ($this->routes as $route) {
            $route->middleware($this->middleware);
        }
    }
}
