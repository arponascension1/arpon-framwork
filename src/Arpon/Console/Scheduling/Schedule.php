<?php

namespace Arpon\Console\Scheduling;

class Schedule
{
    /**
     * All of the scheduled events.
     *
     * @var Event[]
     */
    protected array $events = [];

    /**
     * Add a new command event to the schedule.
     *
     * @param string $command
     * @param  array  $parameters
     * @return Event
     */
    public function command(string $command, array $parameters = []): Event
    {
        $this->events[] = $event = new Event('php artisan '. $command, $parameters);

        return $event;
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return Event[]
     */
    public function events(): array
    {
        return $this->events;
    }
}