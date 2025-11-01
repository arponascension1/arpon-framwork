<?php

namespace Arpon\Filesystem;

class Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function get(string $path): string
    {
        return file_get_contents($path);
    }

    public function put(string $path, string $contents): int|false
    {
        return file_put_contents($path, $contents);
    }

    public function delete(string $path): bool
    {
        return unlink($path);
    }
}
