<?php

namespace Arpon\Routing;

use Arpon\Http\Request;

class Route
{
    public string $method;
    public string $uri;
    public mixed $action;
    public array $middleware = [];
    protected array $wheres = [];
    protected array $parameters = [];
    protected ?string $name = null;
    protected Router $router; // Added router property

    /**
     * Add a where constraint to the route.
     *
     * @param  string|array  $name
     * @param  string|null  $expression
     * @return $this
     */
    public function where(string|array $name, string $expression = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->wheres[$key] = $value;
            }
        } else {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    public function __construct(string $method, string $uri, mixed $action, Router $router) // Added Router $router
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
        $this->router = $router; // Assign router
    }

    /**
     * Set the middleware for the route.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function middleware(array|string $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        $this->router->addNamedRoute($this); // Register with the router
        return $this;
    }

    public function matches(Request $request): bool
    {
        $pattern = $this->compileRouteUri($this->uri);

        if (preg_match($pattern, $request->path(), $matches)) {
            $this->parameters = $this->parseParameters($matches);
            return true;
        }

        return false;
    }

    protected function compileRouteUri(string $uri): string
    {
        // Convert URI to a regex pattern
        // Replace {param} with a regex to capture the parameter value
        $pattern = preg_replace('/{([a-zA-Z0-9_]+)}/', '([a-zA-Z0-9_.-]+)', $uri);
        return "#^" . str_replace('/', '\\/', $pattern) . "$#";
    }

    protected function parseParameters(array $matches): array
    {
        // Remove the full match (index 0)
        array_shift($matches);
        return $matches;
    }

    /**
     * Get the route parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}