<?php

namespace Arpon\Routing;

class ResourceRegistration
{
    /**
     * The router instance.
     *
     * @var Router
     */
    protected Router $router;

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
     * Create a new resource registration instance.
     *
     * @param Router $router
     * @param string $name
     * @param string $controller
     * @param  array  $options
     * @return void
     */
    public function __construct(Router $router, string $name, string $controller, array $options = [])
    {
        $this->router = $router;
        $this->name = $name;
        $this->controller = $controller;

        $this->addResourceRoutes();
    }

    /**
     * Add the resource routes.
     *
     * @return void
     */
    protected function addResourceRoutes(): void
    {
        $resource = str_replace('/', '.', $this->name);

        $uriSegments = explode('/', $this->name);
        $lastSegment = end($uriSegments);
        $paramName = rtrim($lastSegment, 's');

        $this->routes[] = $this->router->get($this->name, [$this->controller, 'index'])->name("{$resource}.index");
        $this->routes[] = $this->router->get("{$this->name}/create", [$this->controller, 'create'])->name("{$resource}.create");
        $this->routes[] = $this->router->post($this->name, [$this->controller, 'store'])->name("{$resource}.store");
        $this->routes[] = $this->router->get("{$this->name}/{{$paramName}}", [$this->controller, 'show'])->name("{$resource}.show");
        $this->routes[] = $this->router->get("{$this->name}/{{$paramName}}/edit", [$this->controller, 'edit'])->name("{$resource}.edit");
        $this->routes[] = $this->router->put("{$this->name}/{{$paramName}}", [$this->controller, 'update'])->name("{$resource}.update");
        $this->routes[] = $this->router->patch("{$this->name}/{{$paramName}}", [$this->controller, 'update']);
        $this->routes[] = $this->router->delete("{$this->name}/{{$paramName}}", [$this->controller, 'destroy'])->name("{$resource}.destroy");
    }

    /**
     * Add middleware to the resource routes.
     *
     * @param array|string|null $middleware
     * @return $this
     */
    public function middleware(array|string|null $middleware): static
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        return $this;
    }
}
