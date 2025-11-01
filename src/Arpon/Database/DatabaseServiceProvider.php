<?php

namespace Arpon\Database;

use Arpon\Database\Connectors\ConnectionFactory;
use Arpon\Database\Eloquent\Model;
use Arpon\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });
    }

    public function boot(): void
    {
        Model::setConnectionResolver($this->app['db']);
    }
}
