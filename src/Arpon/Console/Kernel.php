<?php

namespace Arpon\Console;
use Arpon\Console\Commands\MakeMigrationCommand;
use Arpon\Console\Commands\MigrateCommand;
use Arpon\Console\Commands\RouteListCommand;
use Arpon\Console\Commands\ServeCommand;
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

            'serve' => ServeCommand::class,
            'route:list' => RouteListCommand::class,
            'migrate' => MigrateCommand::class,
            'make:migration' => MakeMigrationCommand::class,
            'db:wipe' => WipeCommand::class,
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
