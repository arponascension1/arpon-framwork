<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Routing\RouteCache;

class RouteClearCommand extends Command
{
    protected string $signature = 'route:clear';
    protected string $description = 'Clear the cached routes';

    public function handle(): int
    {
        $basePath = $this->app->basePath();
        $cachePath = $basePath . '/bootstrap/cache/routes.php';
        $routeCache = new RouteCache($cachePath);

        if ($routeCache->clear()) {
            $this->info('Route cache cleared successfully!');
            return 0;
        }

        $this->error('Failed to clear route cache.');
        return 1;
    }
}
