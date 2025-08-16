<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Http\File\UploadedFile;

class Image implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return false;
        }

        return str_starts_with($value->getMimeType(), 'image/');
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} must be an image.";
    }
}
