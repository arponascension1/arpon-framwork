<?php

namespace Arpon\Contracts\Auth;

use Arpon\Database\ORM\Model;

interface UserProvider
{
    public function retrieveById(mixed $identifier): ?Model;

    public function retrieveByToken(mixed $identifier, string $token): ?Model;

    public function updateRememberToken(Model $user, string $token);

    public function retrieveByCredentials(array $credentials): ?Model;

    public function validateCredentials(Model $user, array $credentials): bool;
}