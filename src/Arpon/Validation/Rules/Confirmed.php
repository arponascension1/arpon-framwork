<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;

class Confirmed implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        return isset($data[$attribute . '_confirmation']) && $value === $data[$attribute . '_confirmation'];
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} confirmation does not match.";
    }
}
