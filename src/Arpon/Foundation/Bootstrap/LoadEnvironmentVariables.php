<?php

namespace Arpon\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Arpon\Foundation\Application;

class LoadEnvironmentVariables
{
    public function bootstrap(Application $app): void
    {
        $file = defined('PHPUNIT_RUNNING') ? '.env.testing' : $app->environmentFile();

        if (file_exists($app->environmentPath() . '/' . $file)) {
            Dotenv::createImmutable($app->environmentPath(), $file)->load();
        }
    }
}
