<?php

namespace Arpon\View;

use InvalidArgumentException;

class FileViewFinder
{
    /**
     * The array of active view paths.
     *
     * @var array
     */
    protected array $paths = [];

    /**
     * The array of registered view names.
     *
     * @var array
     */
    protected array $views = [];

    /**
     * Create a new file view loader instance.
     *
     * @param  array  $paths
     * @return void
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $view
     * @return string
     * @throws 
     */
    public function find(string $view): string
    {
        if (isset($this->views[$view])) {
            return $this->views[$view];
        }

        if (file_exists($path = $this->findInPaths($view, $this->paths))) {
            return $this->views[$view] = $path;
        }

        throw new InvalidArgumentException("View [{$view}] not found.");
    }

    /**
     * Find the given view in the list of paths.
     *
     * @param  string  $view
     * @param  array  $paths
     * @return string
     * @throws 
     */
    protected function findInPaths(string $view, array $paths): string
    {
        foreach ($paths as $path) {
            if (file_exists($file = $this->getPath($path, $view))) {
                return $file;
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found in any of the provided paths.");
    }

    /**
     * Get the path to a template with the given name.
     *
     * @param  string  $path
     * @param  string  $view
     * @return string
     */
    protected function getPath(string $path, string $view): string
    {
        return $path . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
    }

    /**
     * Add a new path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addPath(string $path): void
    {
        $this->paths[] = $path;
    }

    /**
     * Prepend a new path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function prependPath(string $path): void
    {
        array_unshift($this->paths, $path);
    }

    /**
     * Get the paths for the loader.
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
