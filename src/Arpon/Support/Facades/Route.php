<?php

// src/Arpon/Support/Facades/Route.php

namespace Arpon\Support\Facades;

use Arpon\Routing\RouteGroupBuilder;
use Arpon\Routing\ResourceRegistration;

/**
 * @method static \Arpon\Routing\Route addRoute(string $method, string $uri, mixed $action)
 * @method static \Arpon\Routing\Route get(string $uri, mixed $action)
 * @method static \Arpon\Routing\Route post(string $uri, mixed $action)
 * @method static \Arpon\Routing\Route put(string $uri, mixed $action)
 * @method static \Arpon\Routing\Route patch(string $uri, mixed $action)
 * @method static \Arpon\Routing\Route delete(string $uri, mixed $action)
 * @method static \Arpon\Routing\Route options(string $uri, mixed $action)
 * @method static ResourceRegistration resource(string $uri, string $controller)
 * @method static RouteGroupBuilder middleware(array|string $middleware)
 * @method static RouteGroupBuilder group(array $attributes, \Closure $callback = null)
 *
 * @see \Arpon\Routing\Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
