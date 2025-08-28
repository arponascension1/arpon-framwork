<?php

namespace Arpon\Foundation\Bootstrap;

use Exception;
use Arpon\Config\Repository;
use Arpon\Foundation\Application;

class LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        $app->singleton('config', function () use ($app) {
            return new Repository($this->loadConfigurationFiles($app));
        });
    }

    /**
     * Load the configuration files from the application directory.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @return array
     * @throws \Exception
     */
    protected function loadConfigurationFiles(Application $app): array
    {
        $files = [];
        $configPath = realpath($app->configPath());

        foreach (glob($configPath . '/*.php') as $file) {
            $files[basename($file, '.php')] = $file;
            
        }

        $configuration = [];

        foreach ($files as $key => $path) {
            $configuration[$key] = require $path;
        }

        return $configuration;
    }
}