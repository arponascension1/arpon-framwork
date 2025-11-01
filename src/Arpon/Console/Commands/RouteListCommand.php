<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Routing\Router;

class RouteListCommand extends Command
{
    protected string $signature = 'route:list';
    protected string $description = 'List all registered routes';

    public function handle(): int
    {
        /** @var Router $router */
        $router = app('router');
        $routes = $router->getRoutes();

        $headers = ['Method', 'URI', 'Name', 'Action'];
        $rows = [];

        foreach ($routes as $route) {
            $action = $this->formatAction($route->action);
            $name = $route->getName() ?: '-';
            
            $rows[] = [
                $route->method,
                $route->uri ?: '/',
                $name,
                $action
            ];
        }

        echo "\n";
        $this->info('Application Routes:');
        echo "\n";
        $this->table($headers, $rows);
        echo "\n";

        return 0;
    }

    protected function formatAction($action): string
    {
        if ($action instanceof \Closure) {
            return 'Closure';
        }

        if (is_string($action)) {
            return $action;
        }

        if (is_array($action)) {
            return $action[0] . '@' . $action[1];
        }

        return 'Unknown';
    }
}
