<?php

namespace Arpon\Session;

use Arpon\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('session', function ($app) {
            $config = $app->make(\Arpon\Config\Repository::class);
            return new SessionManager($config);
        });
    }

    public function boot(): void
    {
        //
    }
}
