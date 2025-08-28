<?php

namespace Arpon\Database;

use Arpon\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db', function ($app) {
            $config = $app->make('config')->get('database');
            return new DatabaseManager($config);
        });
    }
}
