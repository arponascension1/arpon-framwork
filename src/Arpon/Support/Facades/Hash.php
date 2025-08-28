<?php

namespace Arpon\Support\Facades;

use Arpon\Hashing\HashManager;

/**
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 */
class Hash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hash';
    }
}
