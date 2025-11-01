<?php

namespace Arpon\Hashing;

use Arpon\Contracts\Hashing\Hasher;
use Arpon\Support\ServiceProvider;
use Arpon\Hashing\HashManager;

class HashServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('hash', function ($app) {
            $hashingConfig = $app->make('config')->get('app.hashing', ['driver' => PASSWORD_BCRYPT]);
            
            return new HashManager($hashingConfig);
        });

        $this->app->singleton(Hasher::class, function ($app) {
            return $app->make('hash');
        });
    }

    public function boot(): void
    {
        //
    }
}
