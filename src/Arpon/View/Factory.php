<?php

// src/Arpon/View/Factory.php

namespace Arpon\View;

use Arpon\Foundation\Application;

class Factory
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The view finder implementation.
     *
     * @var FileViewFinder
     */
    protected FileViewFinder $finder;

    /**
     * The shared view data.
     *
     * @var array
     */
    protected array $shared = [];

    /**
     * The layout for the current view.
     *
     * @var string|null
     */
    protected ?string $layout = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->finder = new FileViewFinder([$this->app->basePath() . '/resources/views']);
    }

    /**
     * Set the layout for the current view.
     *
     * @param  string  $layout
     * @return void
     */
    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Get the layout for the current view.
     *
     * @return string|null
     */
    public function getLayout(): ?string
    {
        return $this->layout;
    }

    /**
     * Create a new view instance.
     *
     * @param  string  $view  The name of the view (e.g., 'home', 'pages.about')
     * @param  array  $data
     * @return View
     */
    public function make(string $view, array $data = []): View
    {
        $path = $this->finder->find($view);

        $data = array_merge($this->shared, $data);

        $viewInstance = new View($this, $path, $data);

        if ($this->layout) {
            $viewInstance->setLayout($this->layout);
            $this->layout = null; // Reset layout after setting it for the view
        }

        return $viewInstance;
    }

    /**
     * Share a data element across all views.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function share(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    /**
     * Get the view finder instance.
     *
     * @return FileViewFinder
     */
    public function getFinder(): FileViewFinder
    {
        return $this->finder;
    }

    /**
     * Create a new view instance for a partial.
     *
     * @param  string  $view  The name of the partial view (e.g., 'partials.navbar')
     * @param  array  $data
     * @return View
     */
    public function makePartial(string $view, array $data = []): View
    {
        $path = $this->finder->find($view);

        $data = array_merge($this->shared, $data);

        return new View($this, $path, $data);
    }
}
