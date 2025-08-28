<?php

namespace Arpon\Http\File;

class UploadedFile
{
    protected string $path;
    protected string $originalName;
    protected string $mimeType;
    protected int $size;
    protected int $error;

    public function __construct(string $path, string $originalName, string $mimeType, int $size, int $error)
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->error = $error;
    }

    public function store($directory, $disk = null): string
    {
        return $this->storeAs($directory, $this->hashName(), $disk);
    }

    public function storeAs($directory, $name, $disk = null): string
    {
        $config = require base_path('config/filesystems.php');
        $diskName = $disk ?? $config['default'];
        $root = $config['disks'][$diskName]['root'];

        $destinationPath = rtrim($directory, '/');
        $fullPath = $root . '/' . $destinationPath;

        $this->move($fullPath, $name);

        return $destinationPath . '/' . $name;
    }

    public function move($directory, $name = null): string
    {
        $target = rtrim($directory, '/') . '/' . ($name ?? $this->getClientOriginalName());

        if (!is_dir($directory)) {
            $parentDirectory = dirname($directory);
            if (!is_writable($parentDirectory)) {
                @chmod($parentDirectory, 0777);
            }

            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" directory. Permissions on parent "%s" might still be insufficient.', $directory, $parentDirectory));
            }
        } elseif (!is_writable($directory)) {
            @chmod($directory, 0777);
            if (!is_writable($directory)) {
                 throw new \RuntimeException(sprintf('Unable to write in the "%s" directory even after attempting to set permissions.', $directory));
            }
        }

        if (move_uploaded_file($this->path, $target)) {
            @chmod($target, 0666 & ~umask());
            return $target;
        }

        throw new \RuntimeException(sprintf('Could not move the file "%s" to "%s"', $this->path, $target));
    }

    public function hashName(): string
    {
        return uniqid() . '.' . $this->getExtension();
    }

    public function getExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function getClientOriginalExtension(): string
    {
        return $this->getExtension();
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }
}