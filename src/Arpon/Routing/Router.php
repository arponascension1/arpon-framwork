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
    protected ?Route $fallbackRoute = null;
    protected static array $macros = [];
    protected array $patterns = [];
    protected array $binders = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routes = new RouteCollection();
    }

    public function middleware(array|string $middleware): RouteGroupBuilder
    {
        $attributes['middleware'] = is_array($middleware) ? $middleware : [$middleware];
        return new RouteGroupBuilder($attributes, null);
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
        if (! empty($this->groupStack)) {
            $this->routes->prepend($route);
        } else {
            $this->routes->add($route);
        }

        return $route;
    }

    public function group($attributes, Closure $callback = null): RouteGroupBuilder
    {
        if ($attributes instanceof Closure) {
            $callback = $attributes;
            $attributes = [];
        }

        return new RouteGroupBuilder($attributes, $callback);
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

        $lastGroup = end($this->groupStack);
        $merged = array_merge_recursive($lastGroup, $attributes);

        if (isset($merged['prefix']) && is_array($merged['prefix'])) {
            $merged['prefix'] = implode('/', array_map(fn($p) => trim($p, '/'), $merged['prefix']));
        }

        // Handle 'as' (name prefix) merging specifically
        if (isset($lastGroup['as']) && isset($attributes['as'])) {
            $merged['as'] = $lastGroup['as'] . $attributes['as'];
        } elseif (isset($lastGroup['as'])) {
            $merged['as'] = $lastGroup['as'];
        } elseif (isset($attributes['as'])) {
            $merged['as'] = $attributes['as'];
        }

        return $merged;
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
        return new ResourceRegistration($uri, $controller);
    }

    /**
     * Register an API resource controller.
     * 
     * @param string $uri
     * @param string $controller
     * @return ResourceRegistration
     */
    public function apiResource(string $uri, string $controller): ResourceRegistration
    {
        return new ResourceRegistration($uri, $controller, ['except' => ['create', 'edit']]);
    }

    /**
     * Register a route that responds to multiple HTTP methods.
     * 
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return array
     */
    public function match(array $methods, string $uri, mixed $action): array
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[] = $this->addRoute(strtoupper($method), $uri, $action);
        }
        return $routes;
    }

    /**
     * Register a route that responds to all HTTP methods.
     * 
     * @param string $uri
     * @param mixed $action
     * @return array
     */
    public function any(string $uri, mixed $action): array
    {
        return $this->match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Register a redirect route.
     * 
     * @param string $uri
     * @param string $destination
     * @param int $status
     * @return Route
     */
    public function redirect(string $uri, string $destination, int $status = 302): Route
    {
        return $this->any($uri, function() use ($destination, $status) {
            return redirect($destination, $status);
        })[0];
    }

    /**
     * Register a route that returns a view.
     * 
     * @param string $uri
     * @param string $view
     * @param array $data
     * @return Route
     */
    public function view(string $uri, string $view, array $data = []): Route
    {
        return $this->get($uri, function() use ($view, $data) {
            return view($view, $data);
        });
    }

    /**
     * Register a permanent redirect route.
     * 
     * @param string $uri
     * @param string $destination
     * @return Route
     */
    public function permanentRedirect(string $uri, string $destination): Route
    {
        return $this->redirect($uri, $destination, 301);
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

        // Use fallback route if no route found
        if (! $route) {
            if ($this->fallbackRoute && $this->fallbackRoute->matches($request)) {
                $route = $this->fallbackRoute;
            } else {
                throw new NotFoundHttpException('No route found for URI: ' . $request->path());
            }
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
        $requestMethod = $request->method();

        // Check for method spoofing if it's a POST request
        if ($requestMethod === 'POST') {
            $spoofedMethod = $request->input('_method');
            if ($spoofedMethod) {
                $requestMethod = strtoupper($spoofedMethod);
            }
        }

        foreach ($this->routes as $route) {
            if ($route->method === $requestMethod && $route->matches($request)) {
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
                    // Check if it's a Model class (Eloquent models extend Arpon\Database\Eloquent\Model)
                    if (class_exists($className) && is_subclass_of($className, '\Arpon\Database\Eloquent\Model')) {
                        $instance = $className::find($routeParameters[$paramName]);
                        if ($instance) {
                            $dependencies[] = $instance;
                        } else {
                            throw new NotFoundHttpException("No query results for model [{$className}] {$routeParameters[$paramName]}");
                        }
                    } else {
                        $dependencies[] = $routeParameters[$paramName];
                    }
                } elseif ($this->container->has($className)) {
                    $dependencies[] = $this->container->make($className);
                } else {
                    // If there's a matching route parameter, use it for binding
                    // This handles cases where the model class exists but parameter name doesn't match
                    $dependencies[] = null;
                }
            }
            elseif (array_key_exists($paramName, $routeParameters)) {
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

    public function getLatestGroup(): ?array
    {
        if (empty($this->groupStack)) {
            return null;
        }

        return end($this->groupStack);
    }

    public function addNamedRoute(Route $route): void
    {
        $this->routes->addNamedRoute($route); // Delegate to RouteCollection
    }

    /**
     * Register a fallback route.
     *
     * @param  mixed  $action
     * @return Route
     */
    public function fallback(mixed $action): Route
    {
        $this->fallbackRoute = new Route('GET', '{fallback}', $action, $this);
        $this->fallbackRoute->where('fallback', '.*');
        return $this->fallbackRoute;
    }

    /**
     * Get the fallback route.
     *
     * @return Route|null
     */
    public function getFallbackRoute(): ?Route
    {
        return $this->fallbackRoute;
    }

    /**
     * Register a custom macro.
     *
     * @param  string  $name
     * @param  callable  $callback
     * @return void
     */
    public static function macro(string $name, callable $callback): void
    {
        static::$macros[$name] = $callback;
    }

    /**
     * Check if macro exists.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Call a custom macro.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            $macro = static::$macros[$method];
            
            if ($macro instanceof Closure) {
                return call_user_func_array($macro->bindTo($this, static::class), $parameters);
            }
            
            return call_user_func_array($macro, $parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * Set where patterns for multiple route parameters globally.
     *
     * @param  array  $patterns
     * @return void
     */
    public function pattern(array $patterns): void
    {
        foreach ($patterns as $key => $pattern) {
            $this->patterns[$key] = $pattern;
        }
    }

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  Route  $route
     * @return Route
     */
    public function substituteBindings(Route $route): Route
    {
        foreach ($route->parameters() as $key => $value) {
            if (isset($this->binders[$key])) {
                $binder = $this->binders[$key];
                
                if ($binder instanceof Closure) {
                    $route->setParameter($key, $binder($value));
                } elseif (is_string($binder)) {
                    $route->setParameter($key, (new $binder)->find($value));
                }
            }
        }

        return $route;
    }

    /**
     * Register a model binder for a wildcard.
     *
     * @param  string  $key
     * @param  string  $class
     * @param  Closure|null  $callback
     * @return void
     */
    public function model(string $key, string $class, ?Closure $callback = null): void
    {
        $this->bind($key, function ($value) use ($class, $callback) {
            if ($value === null) {
                return null;
            }

            $instance = (new $class)->find($value);

            if ($instance) {
                return $instance;
            }

            if ($callback) {
                return $callback($value);
            }

            throw new NotFoundHttpException;
        });
    }

    /**
     * Register a custom parameter binder.
     *
     * @param  string  $key
     * @param  Closure|string  $binder
     * @return void
     */
    public function bind(string $key, Closure|string $binder): void
    {
        $this->binders[$key] = $binder;
    }
}
