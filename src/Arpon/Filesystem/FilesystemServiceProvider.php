<?php

namespace Arpon\Filesystem;

use Arpon\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('files', function () {
            return new Filesystem();
        });
    }
}
