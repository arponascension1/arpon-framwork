<?php

namespace Arpon\Http;

use App\Exceptions\Handler;
use Arpon\Container\Container;
use Arpon\Http\Exceptions\NotFoundHttpException;
use Arpon\Pipeline\Pipeline;
use Arpon\Routing\Route;
use Arpon\Routing\Router;
use ReflectionException;

class Kernel
{
    /**
     * The application instance.
     *
     * @var Container
     */
    protected Container $app;

    /**
     * The router instance.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected array $middlewareGroups = [];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected array $routeMiddleware = [];

    /**
     * Create a new HTTP kernel instance.
     *
     * @param Container $app
     * @param Router $router
     * @return void
     */
    public function __construct(Container $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        try {
            app('session')->startSession();
            $route = $this->router->findRoute($request);

            if (is_null($route)) {
                throw new NotFoundHttpException('Route not found.');
            }

            $resolvedMiddleware = $this->resolveMiddlewareStack($route);

            $response = (new Pipeline($this->app))
                ->send($request)
                ->through($resolvedMiddleware)
                ->then(function ($request) use ($route) {
                    return $this->router->runRouteAction($request, $route);
                });

            if (!$response instanceof Response) {
                $response = new Response($response);
            }

            return $response;
        } catch (\Exception $e) {
            $handler = new Handler();
            $response = $handler->render($request, $e);
        } finally {
            app('session')->clearFlashedData(); // Clear data consumed in this request
            app('session')->saveSession(); // Prepare data for the next request
        }

        return $response;
    }

    /**
     * @throws ReflectionException
     * @throws NotFoundHttpException
     */
    protected function sendRequestThroughRouter(Request $request): Response
    {
        return $this->router->dispatch($request);
    }

    /**
     * Terminate the application.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function terminate(Request $request, Response $response): void
    {
        // Perform any final actions here, e.g., session saving
    }

    /**
     * Resolve the middleware stack.
     *
     * @param Route|null $route
     * @return array
     */
    protected function resolveMiddlewareStack(?Route $route): array
    {
        $allMiddleware = $this->middleware;

        if ($route) {
            $allMiddleware = array_merge($allMiddleware, $route->middleware);
        }

        $resolvedMiddleware = [];
        foreach ($allMiddleware as $middlewareItem) {
            if (is_string($middlewareItem)) {
                // Check if it's a middleware group
                if (isset($this->middlewareGroups[$middlewareItem])) {
                    foreach ($this->middlewareGroups[$middlewareItem] as $groupMiddleware) {
                        $resolvedMiddleware[] = $groupMiddleware;
                    }
                    continue; // Skip to the next middlewareItem
                }

                $parts = explode(':', $middlewareItem, 2);
                $aliasOrClass = $parts[0];
                $parameters = $parts[1] ?? null;

                if (isset($this->routeMiddleware[$aliasOrClass])) {
                    $className = $this->routeMiddleware[$aliasOrClass];
                } else {
                    $className = $aliasOrClass; // Assume it's a full class name
                }

                if ($parameters) {
                    $resolvedMiddleware[] = $className . ':' . $parameters;
                } else {
                    $resolvedMiddleware[] = $className;
                }
            } else {
                // If it's not a string (e.g., a Closure or an object), add it directly
                $resolvedMiddleware[] = $middlewareItem;
            }
        }

        return $resolvedMiddleware;
    }
}
