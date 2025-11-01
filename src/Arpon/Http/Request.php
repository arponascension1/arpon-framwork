<?php

namespace Arpon\Http;

use Arpon\Session\SessionManager;
use Arpon\Validation\Validator;
use Arpon\Validation\ValidationException;
use Arpon\Http\HeaderBag;
use Arpon\Http\File\UploadedFile;
use Arpon\Http\RedirectResponse;

class Request
{
    protected array $query;
    protected array $request;
    public HeaderBag $headers;
    protected string $method;
    protected string $pathInfo;
    protected array $routeParameters = [];

    protected array $json = [];
    protected array $files = [];

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->headers = new HeaderBag(function_exists('getallheaders') ? getallheaders() : []);

        // Determine the HTTP method, considering method spoofing
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->method = $_SERVER['REQUEST_METHOD'];
            if ($this->method === 'POST' && isset($this->request['_method'])) {
                $this->method = strtoupper($this->request['_method']);
            }
        } else {
            $this->method = 'GET'; // Default to GET if REQUEST_METHOD is not set
        }

        $this->pathInfo = $this->preparePathInfo();

        $this->files = $this->normalizeFiles($_FILES);

        $this->parseInput();
    }

    protected function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['tmp_name'])) {
                $normalized[$key] = $this->normalizeMultipleFiles($file);
            } else {
                $normalized[$key] = new UploadedFile(
                    $file['tmp_name'],
                    $file['name'],
                    $file['type'],
                    $file['size'],
                    $file['error']
                );
            }
        }

        return $normalized;
    }

    protected function normalizeMultipleFiles(array $file): array
    {
        $normalized = [];

        foreach ($file['tmp_name'] as $index => $tmpName) {
            $normalized[] = new UploadedFile(
                $tmpName,
                $file['name'][$index],
                $file['type'][$index],
                $file['size'][$index],
                $file['error'][$index]
            );
        }

        return $normalized;
    }

    protected function parseInput(): void
    {
        if ($this->isJson()) {
            $content = file_get_contents('php://input');
            $this->json = json_decode($content, true) ?? [];
        }
    }

    public static function capture(): static
    {
        return new static();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->pathInfo;
    }

    protected function preparePathInfo(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        $path = str_replace($queryString, '', $requestUri);
        return trim($path, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request, $this->files, $this->json, $this->routeParameters);
    }

    public function json(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
    }

    /**
     * Retrieve a query string item from the request.
     */
    public function query(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Retrieve a GET input item from the request.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query($key, $default);
    }

    /**
     * Retrieve a POST input item from the request.
     */
    public function post(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->request;
        }

        return $this->request[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers->get($key, $default);
    }

    public function session(): SessionManager
    {
        return app('session');
    }

    public function validate(array $rules, array $messages = []): array
    {
        $validator = Validator::make($this->all(), $rules, $messages);

        try {
            return $validator->validate();
        } catch (ValidationException $e) {
            $this->session()->flash('errors', $e->errors());
            $this->session()->flashInput($this->all());

            // Redirect back to the previous page
            $response = new RedirectResponse($this->session()->previousUrl());
            $response->send();
            exit(); // Terminate script execution after redirect
        }
    }

    public function ajax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type'), 'json');
    }

    public function fullUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Determine if the request contains a given input item key.
     */
    public function has(string $key): bool
    {
        return isset($this->request[$key]) || isset($this->query[$key]);
    }

    /**
     * Get a subset of the items from the input.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get all of the input except for a specified array of items.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Get the client IP address.
     */
    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get the URL (no query string).
     */
    public function url(): string
    {
        return strtok($this->fullUrl(), '?');
    }

    /**
     * Determine if the current URL matches a given pattern.
     */
    public function is(string $pattern): bool
    {
        $path = $this->path();
        $pattern = str_replace('*', '.*', $pattern);

        return (bool)preg_match("#^{$pattern}$#", $path);
    }

    /**
     * Dynamically retrieve values from the input.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Determine if the request contains any of the given input item keys.
     */
    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if (isset($this->request[$key]) || isset($this->query[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given input key is present and not empty.
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return !is_null($value) && $value !== '';
    }

    /**
     * Determine if the given input key is not present.
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Retrieve a boolean value from the request.
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a route parameter.
     */
    public function route(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->routeParameters;
        }

        return $this->routeParameters[$key] ?? $default;
    }

    /**
     * Get all of the segments of the URI.
     */
    public function segments(): array
    {
        $segments = explode('/', $this->path());

        return array_filter($segments, fn($value) => $value !== '');
    }

    /**
     * Get a specific segment from the URI.
     */
    public function segment(int $index, mixed $default = null): mixed
    {
        $segments = $this->segments();

        return $segments[$index - 1] ?? $default;
    }

    /**
     * Retrieve a cookie from the request.
     */
    public function cookie(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $_COOKIE;
        }

        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Retrieve a file from the request.
     */
    public function file(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->files;
        }

        return $this->files[$key] ?? $default;
    }

    /**
     * Determine if a file is present on the request.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key] instanceof UploadedFile && $this->files[$key]->isValid();
    }
}