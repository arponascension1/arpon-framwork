<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Routing\Router;
use Arpon\Routing\RouteCache;

class RouteCacheCommand extends Command
{
    protected string $signature = 'route:cache';
    protected string $description = 'Cache the application routes for faster registration';

    public function handle(): int
    {
        $this->info('Caching routes...');

        /** @var Router $router */
        $router = app('router');
        
        $basePath = $this->app->basePath();
        $cachePath = $basePath . '/bootstrap/cache/routes.php';
        $routeCache = new RouteCache($cachePath);

        if ($routeCache->cache($router->getRoutes())) {
            $this->info('Routes cached successfully!');
            echo "Cache file: $cachePath\n";
            return 0;
        }

        $this->error('Failed to cache routes.');
        return 1;
    }
}
