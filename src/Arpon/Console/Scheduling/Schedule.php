<?php

namespace Arpon\Console\Scheduling;

class Schedule
{
    /**
     * All of the scheduled events.
     *
     * @var \Arpon\Console\Scheduling\Event[]
     */
    protected $events = [];

    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Arpon\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        $this->events[] = $event = new Event('php artisan '. $command, $parameters);

        return $event;
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return \Arpon\Console\Scheduling\Event[]
     */
    public function events()
    {
        return $this->events;
    }
}