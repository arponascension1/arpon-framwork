<?php

namespace Arpon\Filesystem;

class LocalFilesystemAdapter implements FilesystemAdapter
{
    protected string $root;

    public function __construct(string $root)
    {
        $this->root = $root;
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        if (file_exists($fullPath)) {
            return @unlink($fullPath);
        }
        return false;
    }
}
