<?php

namespace Arpon\Routing;

use Closure;

class RouteGroupBuilder
{
    public Router $router;
    public array $attributes;
    protected ?Closure $callback;

    public function __construct(Router $router, array $attributes, ?Closure $callback)
    {
        $this->router = $router;
        $this->attributes = $attributes;
        $this->callback = $callback;
    }

    public function group(Closure $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    public function middleware(array|string $middleware): static
    {
        $middleware = (array) $middleware;
        $this->attributes['middleware'] = array_merge($this->attributes['middleware'] ?? [], $middleware);

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = rtrim($this->attributes['prefix'] ?? '', '/') . '/' . ltrim($prefix, '/');

        return $this;
    }

    public function __destruct()
    {
        $this->router->_group($this->attributes, $this->callback);
    }
}
