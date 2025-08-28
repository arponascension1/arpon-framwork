<?php

namespace Arpon\Filesystem;

interface FilesystemAdapter
{
    public function delete(string $path): bool;
}
