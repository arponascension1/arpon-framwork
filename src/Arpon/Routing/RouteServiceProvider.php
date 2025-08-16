<?php

namespace Arpon\Routing;
use Arpon\Support\ServiceProvider;
use Exception;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     *
     * @throws Exception
     */
    public function boot(): void
    {
        $router = $this->app->make('router');
        $router->_group([
            'middleware' => 'web',
        ], function ($router) {
            require $this->app->basePath() . '/routes/web.php';

        });
    }

    public function register(): void
    {
        $this->app->singleton('router', function ($app) {
            return new \Arpon\Routing\Router($app);
        });
    }
}
