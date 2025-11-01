<?php

namespace Arpon\Routing;

use Arpon\Container\Container;

class RouteCache
{
    protected string $cachePath;

    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    /**
     * Cache the routes.
     * 
     * @param RouteCollection $routes
     * @return bool
     */
    public function cache(RouteCollection $routes): bool
    {
        $cacheData = [];
        
        foreach ($routes as $route) {
            $cacheData[] = [
                'method' => $route->method,
                'uri' => $route->uri,
                'action' => $this->serializeAction($route->action),
                'middleware' => $route->middleware,
                'name' => $route->getName(),
            ];
        }

        $content = '<?php return ' . var_export($cacheData, true) . ';';
        
        $directory = dirname($this->cachePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($this->cachePath, $content) !== false;
    }

    /**
     * Load cached routes.
     * 
     * @param Router $router
     * @return bool
     */
    public function load(Router $router): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $cacheData = require $this->cachePath;
        
        foreach ($cacheData as $routeData) {
            $route = new Route(
                $routeData['method'],
                $routeData['uri'],
                $this->unserializeAction($routeData['action']),
                $router
            );

            if (!empty($routeData['middleware'])) {
                $route->middleware($routeData['middleware']);
            }

            if (!empty($routeData['name'])) {
                $route->name($routeData['name']);
            }

            $router->getRoutes()->add($route);
        }

        return true;
    }

    /**
     * Check if route cache exists.
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Clear the route cache.
     * 
     * @return bool
     */
    public function clear(): bool
    {
        if ($this->exists()) {
            return unlink($this->cachePath);
        }
        return true;
    }

    /**
     * Serialize the route action for caching.
     * 
     * @param mixed $action
     * @return array
     */
    protected function serializeAction(mixed $action): array
    {
        if ($action instanceof \Closure) {
            return ['type' => 'closure', 'value' => null];
        }

        if (is_string($action)) {
            return ['type' => 'string', 'value' => $action];
        }

        if (is_array($action)) {
            return ['type' => 'array', 'value' => $action];
        }

        return ['type' => 'unknown', 'value' => null];
    }

    /**
     * Unserialize the route action from cache.
     * 
     * @param array $serialized
     * @return mixed
     */
    protected function unserializeAction(array $serialized): mixed
    {
        return match ($serialized['type']) {
            'string' => $serialized['value'],
            'array' => $serialized['value'],
            'closure' => function() {
                throw new \RuntimeException('Closures cannot be cached. Use controller actions instead.');
            },
            default => null,
        };
    }

    /**
     * Get the cache file path.
     * 
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }
}
