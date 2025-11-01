<?php

namespace Arpon\Routing;

use Arpon\Http\RedirectResponse;


class Redirector
{
    /**
     * The URL generator instance.
     *
     * @var UrlGenerator
     */
    protected UrlGenerator $generator;

    protected $session;

    /**
     * Create a new Redirector instance.
     *
     * @param UrlGenerator $generator
     * @return void
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param string $path
     * @param int $status
     * @param array $headers
     * @param bool|null $secure
     * @return RedirectResponse
     */
    public function to(string $path, int $status = 302, array $headers = [], bool $secure = null): RedirectResponse
    {
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    /**
     * Create a new redirect response to the previous URL.
     *
     * @param int $status
     * @param array $headers
     * @param bool|null $fallback
     * @return RedirectResponse
     */
    public function back(int $status = 302, array $headers = [], bool|null $fallback = false): RedirectResponse
    {
        $back = $this->generator->previous($fallback);

        return $this->createRedirect($back, $status, $headers);
    }

    /**
     * Create a new redirect response.
     *
     * @param string $path
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    protected function createRedirect(string $path, int $status, array $headers): RedirectResponse
    {
        $redirect = new RedirectResponse($path, $status, $headers);

        if (isset($this->session)) {
            $redirect->setSession($this->session);
        }

        return $redirect;
    }


    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * Create a new redirect response to a named route.
     *
     * @param  string  $route
     * @param  array   $parameters
     * @param  int     $status
     * @param  array   $headers
     * @return RedirectResponse
     */
    public function route(string $route, array $parameters = [], int $status = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }
}