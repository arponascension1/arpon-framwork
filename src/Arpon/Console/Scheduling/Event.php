<?php

namespace Arpon\Console\Scheduling;

class Event
{
    /**
     * The command to run.
     *
     * @var string
     */
    public $command;

    /**
     * The parameters for the command.
     *
     * @var array
     */
    public $parameters;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return void
     */
    public function __construct($command, array $parameters = [])
    {
        $this->command = $command;
        $this->parameters = $parameters;
    }
}