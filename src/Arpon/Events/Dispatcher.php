<?php

namespace Arpon\Events;

use Closure;
use Arpon\Contracts\Events\Dispatcher as DispatcherContract;

class Dispatcher implements DispatcherContract
{
    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            $this->listeners[$event][] = $this->makeListener($listener);
        }
    }

    /**
     * Make a queueable listener callable.
     *
     * @param  Closure|string  $listener
     * @return Closure
     */
    protected function makeListener($listener)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener);
        }

        return $listener;
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param  string  $listener
     * @return Closure
     */
    protected function createClassListener($listener)
    {
        return function () use ($listener) {
            return call_user_func_array([app()->make($listener), 'handle'], func_get_args());
        };
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = call_user_func_array($listener, (array) $payload);

            if ($halt && ! is_null($response)) {
                return $response;
            }

            if (isset($response)) {
                $responses[] = $response;
            }
        }

        return $halt ? null : $responses;
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0;
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $subscriber->subscribe($this);
    }

    /**
     * Resolve the subscriber instance.
     *
     * @param  object|string  $subscriber
     * @return mixed
     */
    protected function resolveSubscriber($subscriber)
    {
        if (is_object($subscriber)) {
            return $subscriber;
        }

        return app()->make($subscriber);
    }
}
