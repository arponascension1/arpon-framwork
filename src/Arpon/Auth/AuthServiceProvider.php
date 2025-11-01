<?php

namespace Arpon\Auth;

use Arpon\Contracts\Hashing\Hasher;
use Arpon\Support\ServiceProvider;
use Arpon\Session\SessionManager;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('auth', function ($app) {
            $config = $app->make('config');
            $sessionManager = $app->make(SessionManager::class);
            $hasher = $app->make(Hasher::class);
            $request = $app->make(\Arpon\Http\Request::class);

                        return new AuthManager($config, $sessionManager, $hasher, $request);
        });
    }

    public function boot(): void
    {
        //
    }
}
