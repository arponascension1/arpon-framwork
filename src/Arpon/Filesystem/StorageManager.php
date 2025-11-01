<?php

namespace Arpon\Filesystem;

class StorageManager
{
    protected array $disks;
    protected string $defaultDisk;
    protected array $resolvedDisks = [];

    public function __construct()
    {
        $config = require base_path('config/filesystems.php');
        $this->disks = $config['disks'];
        $this->defaultDisk = $config['default'];
    }

    public function disk(string $name = null): FilesystemAdapter
    {
        $name = $name ?? $this->defaultDisk;

        if (!isset($this->disks[$name])) {
            throw new \InvalidArgumentException("Disk [{$name}] not configured.");
        }

        if (!isset($this->resolvedDisks[$name])) {
            $this->resolvedDisks[$name] = $this->createDisk($name);
        }

        return $this->resolvedDisks[$name];
    }

    protected function createDisk(string $name): FilesystemAdapter
    {
        $config = $this->disks[$name];
        if ($config['driver'] === 'local') {
            return new LocalFilesystemAdapter($config['root']);
        }
        throw new \InvalidArgumentException("Driver [{$config['driver']}] not supported.");
    }

    public function __call(string $method, array $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}