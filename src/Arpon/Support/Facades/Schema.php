<?php

namespace Arpon\Support\Facades;

/**
 * @method static void create(string $table, \Closure $callback)
 * @method static void dropIfExists(string $table)
 */
class Schema extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db.schema';
    }
}
