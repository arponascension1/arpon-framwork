<?php

namespace Arpon\Console;

use Arpon\Container\Container;
use Arpon\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
    /**
     * The Laravel application instance.
     *
     * @var \Arpon\Container\Container
     */
    protected $laravel;

    /**
     * The event dispatcher instance.
     *
     * @var \Arpon\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new Artisan console application.
     *
     * @param  \Arpon\Container\Container  $laravel
     * @param  \Arpon\Contracts\Events\Dispatcher  $events
     * @param  string  $version
     * @return void
     */
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        parent::__construct('Laravel Artisan', $version);

        $this->laravel = $laravel;
        $this->events = $events;

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        return parent::run($input, $output);
    }

    /**
     * Resolve the given commands.
     *
     * @param  array|mixed  $commands
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->add($this->laravel->make($command));
        }

        return $this;
    }

    /**
     * Get the Laravel application instance.
     *
     * @return \Arpon\Container\Container
     */
    public function getLaravel()
    {
        return $this->laravel;
    }
}