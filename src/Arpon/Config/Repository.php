<?php

// src/Arpon/Config/Repository.php

namespace Arpon\Config;

use Arpon\Foundation\Application;

/**
 * Manages the application's configuration values.
 */
class Repository
{
    /**
     * The application instance.
     *
     * @var \Arpon\Foundation\Application
     */
    protected Application $app;

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // To access nested values using "dot" notation
        $keys = explode('.', $key);
        $data = $this->items;

        foreach ($keys as $segment) {
            if (is_array($data) && isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * Set a given configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }
}
