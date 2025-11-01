<?php

namespace Arpon\Http\Middleware;

use Arpon\Http\Response;
use Closure;
use Arpon\Http\Request;
use Arpon\Security\Csrf;
use Arpon\Session\TokenMismatchException;
use Random\RandomException;

class VerifyCsrfToken
{
    protected Csrf $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }
    /**
     * @throws TokenMismatchException|RandomException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->csrf->isReading($request) || $this->csrf->tokensMatch($request)) {
            return $this->csrf->addCookieToResponse($request, $next($request));
        }
        throw new TokenMismatchException('CSRF token mismatch.');
    }
}
