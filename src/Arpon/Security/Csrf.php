<?php

namespace Arpon\Security;

use Arpon\Http\Request;
use Arpon\Http\Response;
use Arpon\Http\Cookie;
use Random\RandomException;

class Csrf
{
    public function isReading(Request $request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    public function tokensMatch(Request $request): bool
    {
        $sessionToken = $request->session()->get('_token');
        $requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $sessionToken || ! $requestToken) {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * @throws RandomException
     */
    public function addCookieToResponse(Request $request, Response $response): Response
    {
        $config = app('config');

        $response->header(
            'Set-Cookie',
            (string) new Cookie(
                'XSRF-TOKEN',
                $request->session()->token(),
                $this->getCookieExpirationDate(),
                $config->get('session.path'),
                (string) ($config->get('session.domain') ?? ''),
                (bool) $config->get('session.secure'),
                false,
                $config->get('session.same_site', 'lax')
            )
        );

        return $response;
    }

    protected function getCookieExpirationDate(): int
    {
        $config = app('config');

        return time() + ($config->get('session.lifetime') * 60);
    }
}
