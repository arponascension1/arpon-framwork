<?php

namespace Arpon\Validation\Rules;

use Arpon\Http\File\UploadedFile;
use Arpon\Contracts\Validation\ValidationRule;

class Max implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        $max = (int) ($parameters[0] ?? 0);

        if ($value instanceof UploadedFile) {
            return $value->getSize() / 1024 <= $max; // Convert bytes to kilobytes
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        if (is_numeric($value)) {
            return $value <= $max;
        }

        return false;
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        $max = (int) ($parameters[0] ?? 0);

        if ($value instanceof UploadedFile) {
            return "The {$attribute} must not be greater than {$max} kilobytes.";
        }

        return "The {$attribute} must not be greater than {$max}.";
    }
}
