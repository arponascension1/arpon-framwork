<?php

namespace Arpon\Http;

class Cookie
{
    private string $name;
    private string $value;
    private int $expire;
    private string $path;
    private string $domain;
    private bool $secure;
    private bool $httpOnly;
    private string $sameSite;

    public function __construct(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expire = $expire;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    public function __toString(): string
    {
        $cookieString = urlencode($this->name) . '=' . urlencode($this->value);

        if ($this->expire !== 0) {
            $cookieString .= '; expires=' . gmdate('D, d M Y H:i:s T', $this->expire);
        }

        if ($this->path) {
            $cookieString .= '; path=' . $this->path;
        }

        if ($this->domain) {
            $cookieString .= '; domain=' . $this->domain;
        }

        if ($this->secure) {
            $cookieString .= '; secure';
        }

        if ($this->httpOnly) {
            $cookieString .= '; httponly';
        }

        if ($this->sameSite) {
            $cookieString .= '; samesite=' . $this->sameSite;
        }

        return $cookieString;
    }
}
