<?php

namespace Arpon\Support\Facades;

use Arpon\Filesystem\FilesystemAdapter;

/**
 * @method static FilesystemAdapter disk(string $name = null)
 * @method static bool delete(string $path)
 * @see \Arpon\Filesystem\StorageManager
 */
class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'storage';
    }
}
