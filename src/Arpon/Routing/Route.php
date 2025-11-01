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
    protected array $defaults = [];
    protected ?string $name = null;
    protected ?string $domain = null;
    
    protected Router $router;
    protected array $parameterNames = [];

    /**
     * Add a where constraint to the route.
     *
     * @param  string|array  $name
     * @param  string|null  $expression
     * @return $this
     */
    public function where(string|array $name, ?string $expression = null): static
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

    /**
     * Add numeric constraint for route parameter.
     *
     * @param  string|array  $parameters
     * @return $this
     */
    public function whereNumber(string|array $parameters): static
    {
        return $this->assignWhereConstraints($parameters, '[0-9]+');
    }

    /**
     * Add alpha constraint for route parameter.
     *
     * @param  string|array  $parameters
     * @return $this
     */
    public function whereAlpha(string|array $parameters): static
    {
        return $this->assignWhereConstraints($parameters, '[a-zA-Z]+');
    }

    /**
     * Add alphanumeric constraint for route parameter.
     *
     * @param  string|array  $parameters
     * @return $this
     */
    public function whereAlphaNumeric(string|array $parameters): static
    {
        return $this->assignWhereConstraints($parameters, '[a-zA-Z0-9]+');
    }

    /**
     * Add UUID constraint for route parameter.
     *
     * @param  string|array  $parameters
     * @return $this
     */
    public function whereUuid(string|array $parameters): static
    {
        return $this->assignWhereConstraints(
            $parameters, 
            '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
        );
    }

    /**
     * Add ULID constraint for route parameter.
     *
     * @param  string|array  $parameters
     * @return $this
     */
    public function whereUlid(string|array $parameters): static
    {
        return $this->assignWhereConstraints($parameters, '[0-9A-HJKMNP-TV-Z]{26}');
    }

    /**
     * Add in constraint for route parameter (allow only specific values).
     *
     * @param  string  $parameter
     * @param  array  $values
     * @return $this
     */
    public function whereIn(string $parameter, array $values): static
    {
        return $this->where($parameter, implode('|', array_map('preg_quote', $values)));
    }

    /**
     * Assign where constraints to parameters.
     *
     * @param  string|array  $parameters
     * @param  string  $expression
     * @return $this
     */
    protected function assignWhereConstraints(string|array $parameters, string $expression): static
    {
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        foreach ($parameters as $parameter) {
            $this->where($parameter, $expression);
        }

        return $this;
    }

    /**
     * Set default value(s) for route parameter(s).
     *
     * @param  string|array  $name
     * @param  mixed  $value
     * @return $this
     */
    public function defaults(string|array $name, mixed $value = null): static
    {
        if (is_array($name)) {
            $this->defaults = array_merge($this->defaults, $name);
        } else {
            $this->defaults[$name] = $value;
        }

        return $this;
    }

    /**
     * Set the domain constraint for the route.
     *
     * @param  string  $domain
     * @return $this
     */
    public function domain(string $domain): static
    {
        $this->domain = $domain;
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
        if (isset($latestGroup['as'])) {
            $name = $latestGroup['as'] . $name;
        }

        $this->name = $name;
        $this->router->addNamedRoute($this); // Register with the router
        return $this;
    }

    public function matches(Request $request): bool
    {
        // Check domain constraint first
        if ($this->domain !== null) {
            $host = $request->getHost();
            $domainPattern = '#^' . str_replace(
                ['.', '{', '}'],
                ['\.', '(?P<', '>[^.]+)'],
                $this->domain
            ) . '$#';
            
            if (!preg_match($domainPattern, $host)) {
                return false;
            }
        }

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
            $uri = '';
        }

        // Clear previous parameter names
        $this->parameterNames = [];

        // Handle optional parameters (e.g., {id?})
        // Convert URI to a regex pattern and capture parameter names
        $pattern = preg_replace_callback(
            '/{([a-zA-Z0-9_]+)(\?)?}/',
            function ($matches) {
                $paramName = $matches[1];
                $optional = isset($matches[2]) && $matches[2] === '?';
                
                $this->parameterNames[] = $paramName;
                
                // Check if there's a where constraint for this parameter
                if (isset($this->wheres[$paramName])) {
                    $constraint = $this->wheres[$paramName];
                    $regex = "({$constraint})";
                } else {
                    $regex = '([a-zA-Z0-9_.-]+)';
                }
                
                // Make it optional if needed
                return $optional ? "{$regex}?" : $regex;
            },
            $uri
        );

        return "#^" . str_replace('/', '\\/', $pattern) . "$#";
    }

    protected function parseParameters(array $matches): array
    {
        // Remove the full match (index 0)
        array_shift($matches);

        $parameters = [];
        
        // Match parameter names with their values
        foreach ($this->parameterNames as $index => $name) {
            // Check if the parameter was captured (not empty for optional params)
            $value = isset($matches[$index]) && $matches[$index] !== '' ? $matches[$index] : null;
            
            // Use default value if parameter is missing
            if ($value === null && isset($this->defaults[$name])) {
                $value = $this->defaults[$name];
            }
            
            if ($value !== null) {
                $parameters[$name] = $value;
            }
        }

        return $parameters;
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

    /**
     * Set a route parameter.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return $this
     */
    public function setParameter(string $name, mixed $value): static
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
