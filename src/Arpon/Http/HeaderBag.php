<?php

namespace Arpon\Http;

class HeaderBag
{
    protected array $headers;

    public function __construct(array $headers = [])
    {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function all(): array
    {
        return array_map(function ($value) {
            return (array)$value;
        }, $this->headers);
    }

    public function set(string $key, string $value): void
    {
        $this->headers[strtolower($key)] = $value;
    }

    public function normalizeKey(string $key): string
    {
        return str_replace(' ', '-', ucwords(str_replace(['-', '_'], ' ', $key)));
    }
}