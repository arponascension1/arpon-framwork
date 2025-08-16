<?php

namespace Arpon\Foundation\Console;

use Arpon\Console\Application as Artisan;
use Arpon\Console\Scheduling\Schedule; // Assuming this exists or will be created
use Arpon\Contracts\Console\Kernel as KernelContract;
use Arpon\Contracts\Events\Dispatcher;
use Arpon\Contracts\Foundation\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel implements KernelContract
{
    /**
     * The application instance.
     *
     * @var \Arpon\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The Artisan application instance.
     *
     * @var \Arpon\Console\Application
     */
    protected $artisan;

    /**
     * The event dispatcher instance.
     *
     * @var \Arpon\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The commands provided by the application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        \Arpon\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        // Add other bootstrappers as needed
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param  \Arpon\Contracts\Foundation\Application  $app
     * @param  \Arpon\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        $this->app = $app;
        $this->events = $events;

        $this->defineConsoleSchedule();
    }

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->app->instance('Arpon\Console\Scheduling\Schedule', $schedule = new Schedule);

        $this->schedule($schedule);
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->bootstrap();

            return $this->getArtisan()->run($input, $output);
        } catch (\Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Bootstrap the application for console commands.
     *
     * @return void
     */
    protected function bootstrap()
    {
        $this->app->bootstrapWith($this->bootstrappers());
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Get the Artisan application instance.
     *
     * @return \Arpon\Console\Application
     */
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                                ->resolveCommands($this->commands());
        }

        return $this->artisan;
    }

    /**
     * Get the commands to be registered with the Artisan application.
     *
     * @return array
     */
    protected function commands()
    {
        return array_merge($this->commands, $this->app->make('Arpon\Console\Scheduling\Schedule')->commands());
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $this->bootstrap();

        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->getArtisan()->output();
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(\Throwable $e)
    {
        $this->app->make(\App\Exceptions\Handler::class)->report($e);
    }

    /**
     * Render the given exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException(OutputInterface $output, \Throwable $e)
    {
        $this->app->make(\App\Exceptions\Handler::class)->renderForConsole($output, $e);
    }

    /**
     * Terminate the application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate(InputInterface $input, $status)
    {
        $this->app->terminate();
    }
}