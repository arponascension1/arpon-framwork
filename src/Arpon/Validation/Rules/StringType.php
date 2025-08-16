<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;

class StringType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        return is_string($value);
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} must be a string.";
    }
}
