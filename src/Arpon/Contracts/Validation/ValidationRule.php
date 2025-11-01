<?php

namespace Arpon\Contracts\Validation;

interface ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool;

    public function message(string $attribute, mixed $value, array $parameters, array $data): string;
}
