<?php

namespace Arpon\Hashing;

use Arpon\Contracts\Hashing\Hasher;
use RuntimeException;

class HashManager implements Hasher
{
    protected array $config;
    protected Hasher $hasher;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->hasher = $this->createDefaultDriver();
    }

    protected function createDefaultDriver(): Hasher
    {
        return new BcryptHasher();
    }

    public function make(string $value, array $options = []): string
    {
        return $this->hasher->make($value, $options);
    }

    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->hasher->check($value, $hashedValue);
    }

    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->hasher->needsRehash($hashedValue, $options);
    }
}
