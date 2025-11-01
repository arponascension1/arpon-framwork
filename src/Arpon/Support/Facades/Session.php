<?php

namespace Arpon\Support\Facades;

/**
 * @method static void put(string $key, mixed $value)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static void forget(string $key)
 * @method static void flush()
 * @method static string regenerate(bool $destroy = false)
 * @method static string token()
 * @method static void invalidate()
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session';
    }
}
