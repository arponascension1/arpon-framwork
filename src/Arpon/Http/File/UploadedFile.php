<?php

namespace Arpon\Http\File;

use InvalidArgumentException;

class UploadedFile
{
    protected string $path;
    protected string $originalName;
    protected string $mimeType;
    protected int $error;
    protected int $size;

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, ?int $size = null)
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType ?? 'application/octet-stream';
        $this->error = $error ?? UPLOAD_ERR_OK;
        $this->size = $size ?? 0;
    }

    /**
     * Get the original full path of the file.
     */
    public function getPathname(): string
    {
        return $this->path;
    }

    /**
     * Get the original client file name.
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get the client original extension.
     */
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Get the MIME type of the file.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the error code for the uploaded file.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Determine if the file was uploaded successfully.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param  string  $destinationPath
     * @param  string|null  $fileName
     * @return string|false The full path to the new file, or false on failure.
     */
    public function move(string $destinationPath, ?string $fileName = null): string|false
    {
        $fileName = $fileName ?? $this->originalName;
        $destination = rtrim($destinationPath, '/') . '/' . $fileName;

        if (! is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        if ($this->isValid() && move_uploaded_file($this->path, $destination)) {
            return $destination;
        }

        return false;
    }

    /**
     * Get the file extension from the MIME type.
     */
    public function extension(): string
    {
        return mime_content_type($this->path);
    }

    /**
     * Get the file hash.
     */
    public function hashName(?string $path = null): string
    {
        $hash = sha1(time() . $this->getClientOriginalName());
        $extension = $this->getClientOriginalExtension();

        return ($path ? rtrim($path, '/') . '/' : '') . $hash . '.' . $extension;
    }
}
