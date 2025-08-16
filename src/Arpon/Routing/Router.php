<?php

namespace Arpon\Routing;

use Closure;
use Exception;
use Arpon\View\View;
use ReflectionException;
use ReflectionMethod;
use Arpon\Container\Container;
use Arpon\Http\Request;
use Arpon\Http\Response;
use Arpon\Http\Exceptions\NotFoundHttpException;
use Arpon\Routing\RouteCollection;

class Router
{
    protected RouteCollection $routes;
    protected Container $container;
    protected array $groupStack = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routes = new RouteCollection();
    }

    public function middleware(array|string $middleware): RouteGroupBuilder
    {
        $attributes['middleware'] = is_array($middleware) ? $middleware : [$middleware];
        return new RouteGroupBuilder($this, $attributes, null);
    }

    /**
     * Add a route to the router.
     *
     * @param string $method
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function addRoute(string $method, string $uri, mixed $action): Route
    {
        $route = new Route($method, $uri, $action, $this); // Pass $this (Router instance)

        if (! empty($this->groupStack)) {
            $latestGroup = end($this->groupStack);

            if (isset($latestGroup['prefix'])) {
                $route->uri = rtrim($latestGroup['prefix'], '/') . '/' . ltrim($route->uri, '/');
            }

            if (isset($latestGroup['middleware'])) {
                $route->middleware($latestGroup['middleware']);
            }
        }
        $this->routes->add($route);

        return $route;
    }

    public function group($attributes, Closure $callback = null): RouteGroupBuilder
    {
        if ($attributes instanceof Closure) {
            $callback = $attributes;
            $attributes = [];
        }

        return new RouteGroupBuilder($this, $attributes, $callback);
    }

    /**
     * Internal method to handle route group creation.
     *
     * @param array $attributes
     * @param Closure $callback
     * @return void
     */
    public function _group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $this->mergeGroupAttributes($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Merge the given attributes with the last group stack entry.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeGroupAttributes(array $attributes): array
    {
        if (empty($this->groupStack)) {
            return $attributes;
        }

        return array_merge_recursive(end($this->groupStack), $attributes);
    }

    /**
     * Register a GET route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a PATCH route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a DELETE route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register an OPTIONS route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function options(string $uri, mixed $action): Route
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    public function resource(string $uri, string $controller): ResourceRegistration
    {
        return new ResourceRegistration($this, $uri, $controller);
    }

    /**
     * Dispatch the request to the correct route.
     *
     * @param Request $request
     * @return Response
     * @throws NotFoundHttpException|ReflectionException
     */
    public function dispatch(Request $request): Response
    {
        $route = $this->findRoute($request);

        if (! $route) {
            throw new NotFoundHttpException('No route found for URI: ' . $request->path());
        }

        return $this->runRouteAction($request, $route);
    }

    /**
     * Find the route matching a given request.
     *
     * @param Request $request
     * @return Route|null
     */
    public function findRoute(Request $request): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->method === $request->method() && $route->matches($request)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Run the given route action.
     *
     * @param Request $request
     * @param Route $route
     * @return Response
     * @throws ReflectionException|Exception
     */
    public function runRouteAction(Request $request, Route $route): Response
    {
        $action = $route->action;
        $routeParameters = $route->parameters();

        // Set route parameters on the request object
        $request->setRouteParameters($routeParameters);

        $parameters = [];
        $response = null;

        if ($action instanceof Closure) {
            $reflection = new \ReflectionFunction($action);
            $methodParameters = $reflection->getParameters();
            $parameters = $this->resolveMethodDependencies($methodParameters, $request, $routeParameters);
            $response = $reflection->invokeArgs($parameters);
        } elseif (is_string($action)) {
            [$controller, $method] = explode('@', $action);
            // Corrected the unescaped backslash here
            $controller = "App\\Http\\Controllers\\" . $controller; // Adjust namespace as needed

            $controllerInstance = $this->container->make($controller);
            $reflection = new ReflectionMethod($controllerInstance, $method);
            $methodParameters = $reflection->getParameters();
            $parameters = $this->resolveMethodDependencies($methodParameters, $request, $routeParameters);
            $response = $reflection->invokeArgs($controllerInstance, $parameters);
        } elseif (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;
            $controllerInstance = $this->container->make($controllerClass);
            $reflection = new ReflectionMethod($controllerInstance, $method);
            $methodParameters = $reflection->getParameters();
            $parameters = $this->resolveMethodDependencies($methodParameters, $request, $routeParameters);
            $response = $reflection->invokeArgs($controllerInstance, $parameters);
        }

        // Ensure the response is an instance of Response before returning
        if (! $response instanceof Response) {
            if ($response instanceof View) {
                $response = new Response($response->render());
            } else {
                $response = new Response($response);
            }
        }

        return $response;
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function resolveMethodDependencies(array $methodParameters, Request $request, array $routeParameters): array
    {
        $dependencies = [];

        foreach ($methodParameters as $parameter) {
            $paramType = $parameter->getType();
            $paramName = $parameter->getName();

            if ($paramType && !$paramType->isBuiltin()) {
                $className = $paramType->getName();

                if (is_a($className, Request::class, true)) {
                    if (is_a($className, \Arpon\Http\FormRequest::class, true)) {
                        $formRequest = $this->container->make($className);
                        $formRequest->validateResolved(); // This will handle validation and authorization
                        $dependencies[] = $formRequest;
                    } else {
                        $dependencies[] = $request;
                    }
                } elseif (array_key_exists($paramName, $routeParameters)) {
                    // Attempt Route-Model Binding
                    if (class_exists($className) && method_exists($className, 'find')) {
                        $instance = $className::find($routeParameters[$paramName]);
                        if ($instance) {
                            $dependencies[] = $instance;
                        } else {
                            throw new NotFoundHttpException("No query results for model [{$className}] {$routeParameters[$paramName]}");
                        }
                    } else {
                        $dependencies[] = $routeParameters[$paramName];
                    }
                }  elseif ($this->container->has($className)) {
                    $dependencies[] = $this->container->make($className);
                }
            } elseif (array_key_exists($paramName, $routeParameters)) {
                $dependencies[] = $routeParameters[$paramName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                 $dependencies[] = null;
            }
        }

        return $dependencies;
    }

    /**
     * Get all of the routes that have been registered.
     *
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->routes->getByName($name);
    }

    public function getNamedRoutes(): array
    {
        return $this->routes->getNamedRoutes();
    }

    public function addNamedRoute(Route $route): void
    {
        $this->routes->addNamedRoute($route); // Delegate to RouteCollection
    }
}