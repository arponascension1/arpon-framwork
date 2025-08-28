<?php

namespace Arpon\Validation;

class ErrorBag
{
    protected array $messages = [];

    public function add(string $key, string $message): void
    {
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = [];
        }
        $this->messages[$key][] = $message;
    }

    public function has(string $key): bool
    {
        return isset($this->messages[$key]) && !empty($this->messages[$key]);
    }

    public function all(): array
    {
        return $this->messages;
    }

    public function first(string $key): ?string
    {
        return $this->messages[$key][0] ?? null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->messages);
    }

    public function toArray(): array
    {
        return $this->messages;
    }
}