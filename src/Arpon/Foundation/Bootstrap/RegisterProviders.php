<?php

namespace Arpon\Foundation\Bootstrap;

use Arpon\Foundation\Application;

class RegisterProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        $app->registerConfiguredProviders();
    }
}
