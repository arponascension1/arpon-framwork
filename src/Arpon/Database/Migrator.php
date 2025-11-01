<?php

namespace Arpon\Database;

use Arpon\Database\ConnectionResolverInterface as Resolver;
use Arpon\Foundation\Application;

class Migrator
{
    protected Resolver $resolver;
    protected Application $app;

    public function __construct(Resolver $resolver, Application $app)
    {
        $this->resolver = $resolver;
        $this->app = $app;
    }

    public function run($path): void
    {
        $files = $this->getMigrationFiles($path);

        foreach ($files as $file) {
            $migration = require $file;
            $migration->up();
        }
    }

    public function getMigrationFiles($path): array
    {
        return glob($path . '/*_*.php');
    }

    public function resolve($class): Migration
    {
        return new $class;
    }

    public function getMigrationClass($file): string
    {
        $file = basename($file, '.php');
        $class = implode('', array_map('ucfirst', explode('_', substr($file, 18))));

        return $class;
    }
}
