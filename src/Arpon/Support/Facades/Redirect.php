<?php

namespace Arpon\Support\Facades;

use Arpon\Http\RedirectResponse;

/**
 * @method static RedirectResponse to(string $path, int $status = 302, array $headers = [], bool $secure = null)
 * @method static RedirectResponse back(int $status = 302, array $headers = [], bool|null $fallback = false)
 * @method static void setSession($session)
 * @method static RedirectResponse route(string $route, array $parameters = [], int $status = 302, array $headers = [])
 *
 * @see \Arpon\Routing\Redirector
 */
class Redirect extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'redirect';
    }
}
