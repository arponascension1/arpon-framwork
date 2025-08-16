<?php

namespace Arpon\Hashing;

use Arpon\Contracts\Hashing\Hasher;

class BcryptHasher implements Hasher
{
    public function make(string $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_BCRYPT, $options);
    }

    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return password_verify($value, $hashedValue);
    }

    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, $options);
    }
}
