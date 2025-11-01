<?php

namespace Arpon\Foundation\Bootstrap;

use Arpon\Foundation\Application;
use Exception;

class RegisterProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param Application $app
     * @return void
     * @throws Exception
     */
    public function bootstrap(Application $app): void
    {
        $app->registerConfiguredProviders();
    }
}
