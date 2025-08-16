<?php

namespace Arpon\Routing;

use Arpon\Http\Request;

class UrlGenerator
{
    /**
     * The route collection.
     *
     * @var \Arpon\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The request instance.
     *
     * @var \Arpon\Http\Request
     */
    protected $request;

    /**
     * The session store implementation.
     *
     * @var \Arpon\Session\Store|null
     */
    protected $session;

    /**
     * Create a new URL generator instance.
     *
     * @param  \Arpon\Routing\RouteCollection  $routes
     * @param  \Arpon\Http\Request  $request
     * @return void
     */
    public function __construct(RouteCollection $routes, Request $request)
    {
        $this->routes = $routes;
        $this->request = $request;
    }

    /**
     * Get the URL for a given path.
     *
     * @param  string  $path
     * @param  mixed   $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // For simplicity, we'll just return the path for now.
        // In a full framework, this would handle asset URLs, etc.
        return $path;
    }

    /**
     * Get the URL for the previous request.
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false)
    {
        $referrer = $this->request->headers->get('referer');

        $url = $referrer ? $this->to($referrer) : $this->to('/');

        if ($url === $this->current() && $fallback) {
            $url = $this->to($fallback);
        }

        return $url;
    }

    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current()
    {
        return $this->to($this->request->path());
    }

    /**
     * Set the session store implementation.
     *
     * @param  \Arpon\Session\Store  $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Generate a URL for a given named route.
     *
     * @param  string  $name
     * @param  array   $parameters
     * @param  bool    $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $route = $this->routes->getByName($name);

        if (! $route) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $route->uri;

        // Basic parameter replacement
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{'.$key.'}', $value, $uri);
        }

        // Remove any remaining optional parameters
        $uri = preg_replace('/\/{[^}]+?\?}/', '', $uri);


        return $absolute ? $this->to($uri) : $uri;
    }
}