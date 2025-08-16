<?php

namespace Arpon\Routing;

use ArrayIterator;
use IteratorAggregate;

class RouteCollection implements IteratorAggregate
{
    /**
     * The array of routes.
     *
     * @var array
     */
    protected array $routes = [];

    /**
     * The array of named routes.
     *
     * @var array
     */
    protected array $namedRoutes = []; // Added namedRoutes property

    /**
     * Add a Route instance to the collection.
     *
     * @param Route $route
     * @return Route
     */
    public function add(Route $route): Route
    {
        $this->routes[] = $route;

        if ($route->getName()) { // Store named routes
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $route;
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }

    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function addNamedRoute(Route $route): void
    {
        if ($route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }
    }
}