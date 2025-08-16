<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;

class Min implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        $min = (int) ($parameters[0] ?? 0);

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        if (is_numeric($value)) {
            return $value >= $min;
        }

        return false;
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        $min = (int) ($parameters[0] ?? 0);
        return "The {$attribute} must be at least {$min}.";
    }
}
