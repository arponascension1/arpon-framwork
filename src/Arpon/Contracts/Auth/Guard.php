<?php

namespace Arpon\Contracts\Auth;

interface Guard
{
    public function check(): bool;

    public function guest(): bool;

    public function user(): ?Authenticatable;

    public function id(): mixed;

    public function validate(array $credentials = []): bool;

    public function attempt(array $credentials = []): bool;

    public function login(Authenticatable $user): void;

    public function logout(): void;

    public function setUser(Authenticatable $user): void;
}
