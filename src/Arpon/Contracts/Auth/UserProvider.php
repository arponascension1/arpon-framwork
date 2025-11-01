<?php

namespace Arpon\Contracts\Auth;

interface UserProvider
{
    public function retrieveById(mixed $identifier): ?Authenticatable;

    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable;

    public function updateRememberToken(Authenticatable $user, string $token);

    public function retrieveByCredentials(array $credentials): ?Authenticatable;

    public function validateCredentials(Authenticatable $user, array $credentials): bool;
}