<?php

namespace Arpon\Console;
use Arpon\Console\Commands\MakeMigrationCommand;
use Arpon\Console\Commands\MigrateCommand;
use Arpon\Console\Commands\RouteCacheCommand;
use Arpon\Console\Commands\RouteClearCommand;
use Arpon\Console\Commands\RouteListCommand;
use Arpon\Console\Commands\ServeCommand;
use Arpon\Console\Commands\StorageLinkCommand;
use Arpon\Console\Commands\WipeCommand;
use Arpon\Foundation\Application;

class Kernel
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new console kernel instance.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the default commands provided by the framework.
     *
     * @return array
     */
    protected function getDefaultCommands(): array
    {
        return [
            ServeCommand::class,
            RouteListCommand::class,
            RouteCacheCommand::class,
            RouteClearCommand::class,
            MigrateCommand::class,
            MakeMigrationCommand::class,
            WipeCommand::class,
            StorageLinkCommand::class
        ];
    }

    /**
     * Get the commands provided by the application.
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->getDefaultCommands();
    }
}
