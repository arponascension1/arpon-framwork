<?php

namespace Arpon\Contracts\Auth;

interface Authenticatable
{
    public function getAuthIdentifierName(): string;

    public function getAuthIdentifier(): mixed;

    public function getAuthPassword(): string;

    public function getRememberToken(): ?string;

    public function setRememberToken(string $value);
}