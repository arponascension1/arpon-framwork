<?php

namespace Arpon\View;

use InvalidArgumentException;
use Arpon\Filesystem\Filesystem;

class Factory
{
    protected Filesystem $files;
    protected array $viewPaths;
    protected array $data = [];

    public function __construct(Filesystem $files, array|string $viewPath)
    {
        $this->files = $files;
        $this->viewPaths = is_array($viewPath) ? $viewPath : [$viewPath];

        foreach ($this->viewPaths as $key => $path) {
            $this->viewPaths[$key] = rtrim($path, '/');
        }
    }

    public function make(string $view, array $data = []): View
    {
        $path = $this->findView($view);

        return new View($this, $path, $data);
    }

    public function makePartial(string $view, array $data = []): View
    {
        return $this->make($view, $data);
    }

    protected function findView(string $view): string
    {
        foreach ($this->viewPaths as $viewPath) {
            $path = $viewPath . '/' . str_replace('.', '/', $view) . '.php';

            if ($this->files->exists($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found in any of the registered paths.");
    }

    public function share(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function getShared(): array
    {
        return $this->data;
    }
}
