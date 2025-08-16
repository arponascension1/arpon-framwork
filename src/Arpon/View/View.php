<?php



namespace Arpon\View;

use Exception;

class View
{
    /**
     * The path to the view file.
     *
     * @var string
     */
    protected string $path;

    /**
     * The array of data to pass to the view.
     *
     * @var array
     */
    protected array $data;

    /**
     * The section contents.
     *
     * @var array
     */
    protected array $sections = [];

    /**
     * The stack of currently opened sections.
     *
     * @var array
     */
    protected array $sectionStack = [];

    /**
     * The layout that this view extends.
     *
     * @var string|null
     */
    protected ?string $layout = null;

    /**
     * The view factory instance.
     *
     * @var \Arpon\View\Factory
     */
    protected Factory $factory;

    /**
     * The currently rendering view instance.
     *
     * @var self|null
     */
    protected static array $viewStack = [];

    /**
     * The error bag instance.
     *
     * @var \Arpon\Validation\ErrorBag|null
     */
    protected ?\Arpon\Validation\ErrorBag $errorBag = null;

    public function __construct(Factory $factory, string $path, array $data = [])
    {
        $this->factory = $factory;
        $this->path = $path;
        $this->data = $data;
    }

    /**
     * Get the error bag instance.
     *
     * @return \Arpon\Validation\ErrorBag|null
     */
    public function getErrorBag(): ?\Arpon\Validation\ErrorBag
    {
        return $this->errorBag;
    }

    /**
     * Render the view.
     *
     * @return string
     * @throws \Exception
     */
    public function render(): string
    {
        if (!file_exists($this->path)) {
            throw new Exception("View file not found at path: {$this->path}");
        }

        // Set the current view instance for helper functions.
        self::setCurrent($this);

        // Make the data array keys available as variables in the view.
        extract($this->data);

        // Add errors from session if available
        $errors = app('session')->get('errors');
        if ($errors) {
            $errorBag = new \Arpon\Validation\ErrorBag();
            foreach ($errors as $key => $messages) {
                foreach ($messages as $message) {
                    $errorBag->add($key, $message);
                }
            }
            $this->errorBag = $errorBag;
            extract(['errors' => $errorBag]);
        }

        // Start output buffering.
        ob_start();

        // Include the view file.
        require $this->path;

        // Get the contents of the buffer.
        $content = ob_get_clean();

        

        // If a layout is extended, render it.
        if ($this->layout) {
            $layoutView = $this->factory->make($this->layout, $this->data);
            $layoutView->sections = array_merge($layoutView->sections, $this->sections); // Pass all sections to the layout
            $content = $layoutView->render();
        }

        // Clear the current view instance.
        self::setCurrent(null);

        return $content;
    }

    /**
     * Start a new section.
     *
     * @param string $section
     * @return void
     */
    public function section(string $section): void
    {
        $this->sectionStack[] = $section;

        ob_start();
    }

    /**
     * Stop the current section.
     *
     * @return void
     */
    public function endSection(): void
    {
        $last = array_pop($this->sectionStack);

        $this->sections[$last] = ob_get_clean();
    }

    /**
     * Get the content of a section.
     *
     * @param string $section
     * @param string $default
     * @return string
     */
    public function yield(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    /**
     * Set the layout that this view extends.
     *
     * @param string $layoutName
     * @return void
     */
    public function extends(string $layoutName): void
    {
        $this->layout = $layoutName;
    }

    /**
     * Set the layout for the current view.
     *
     * @param  string  $layout
     * @return void
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * When a View object is treated as a string, render it.
     *
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Get the path of the view file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the path of the view file.
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the data for the view.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the data for the view.
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Add a piece of data to the view.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function with(string $key, mixed $value = null): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a piece of data from the view.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Determine if a piece of data is present in the view.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get the currently rendering view instance.
     *
     * @return self|null
     */
    public static function getCurrent(): ?self
    {
        return end(self::$viewStack);
    }

    /**
     * Set the currently rendering view instance.
     *
     * @param  self|null  $view
     * @return void
     */
    public static function setCurrent(?self $view): void
    {
        if ($view) {
            self::$viewStack[] = $view;
        } else {
            array_pop(self::$viewStack);
        }
    }
}