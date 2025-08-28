<?php

namespace Arpon\Filesystem;

use Arpon\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('storage', function ($app) {
            return new StorageManager();
        });
    }
}