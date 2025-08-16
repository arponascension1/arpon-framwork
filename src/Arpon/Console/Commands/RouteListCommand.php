<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Routing\RouteCollection;

class RouteListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature = 'route:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered routes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $router = app('router');
        $routes = $router->getRoutes();

        if (empty($routes->getRoutes())) {
            $this->info("Your application doesn't have any routes.");
            return;
        }

        $this->displayRoutes($routes);
    }

    /**
     * Display the routes in a table.
     *
     * @param RouteCollection $routes
     * @return void
     */
    protected function displayRoutes(RouteCollection $routes): void
    {
        $this->table(
            ['Method', 'URI', 'Action', 'Middleware'],
            $this->getRoutesForTable($routes)
        );
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @param RouteCollection $routes
     * @return array
     */
    protected function getRoutesForTable(RouteCollection $routes): array
    {
        $results = [];

        foreach ($routes as $route) {
            $results[] = [
                $route->method,
                $route->uri,
                $this->formatAction($route->action),
                implode(', ', $route->middleware),
            ];
        }

        return $results;
    }

    /**
     * Format the action for display.
     *
     * @param  mixed  $action
     * @return string
     */
    protected function formatAction(mixed $action): string
    {
        if ($action instanceof \Closure) {
            return 'Closure';
        }

        if (is_array($action)) {
            return implode('@', $action);
        }

        return $action;
    }
}
