<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Http\File\UploadedFile;

class Mimes implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return false;
        }

        $allowedMimes = $parameters;

        return in_array($value->getMimeType(), $allowedMimes);
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} must be a file of type: " . implode(', ', $parameters) . ".";
    }
}
