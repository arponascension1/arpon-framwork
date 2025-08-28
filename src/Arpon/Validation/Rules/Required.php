<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;

class Required implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        return !empty($value);
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} field is required.";
    }
}
