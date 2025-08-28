<?php

namespace Arpon\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Arpon\Foundation\Application;

class LoadEnvironmentVariables
{
    public function bootstrap(Application $app): void
    {
        if (file_exists($app->environmentPath() . '/' . $app->environmentFile())) {
            Dotenv::createImmutable($app->environmentPath(), $app->environmentFile())->load();
        }
    }
}
