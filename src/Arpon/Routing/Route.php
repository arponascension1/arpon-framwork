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
    protected array $parameterNames = []; // Moved here

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
        $latestGroup = $this->router->getLatestGroup();
        if (isset($latestGroup['name'])) {
            $name = $latestGroup['name'] . $name;
        }

        $this->name = $name;
        $this->router->addNamedRoute($this); // Register with the router
        return $this;
    }

    public function matches(Request $request): bool
    {
        $routeUri = trim($this->uri, '/');
        $requestPath = trim($request->path(), '/');

        // Handle root path consistency
        if ($routeUri === '' && $requestPath === '/') {
            $requestPath = '';
        }

        $pattern = $this->compileRouteUri($routeUri);

        if (preg_match($pattern, $requestPath, $matches)) {
            $this->parameters = $this->parseParameters($matches);
            return true;
        }

        return false;
    }

    protected function compileRouteUri(string $uri): string
    {
        // If the URI is empty, treat it as the root path
        if (empty($uri)) {
            $uri = ''; // Changed to empty string for consistency with Request::path()
        }

        // Clear previous parameter names
        $this->parameterNames = [];

        // Convert URI to a regex pattern and capture parameter names
        $pattern = preg_replace_callback(
            '/{([a-zA-Z0-9_]+)}/',
            function ($matches) {
                $this->parameterNames[] = $matches[1];
                return '([a-zA-Z0-9_.-]+)';
            },
            $uri
        );

        return "#^" . str_replace('/', '\\/', $pattern) . "$#";
    }

    protected function parseParameters(array $matches): array
    {
        // Remove the full match (index 0)
        array_shift($matches);

        // Create an associative array of parameter names to values
        return array_combine($this->parameterNames, $matches);
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
