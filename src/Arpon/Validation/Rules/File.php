<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Http\File\UploadedFile;

class File implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        return $value instanceof UploadedFile && $value->isValid();
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} must be a valid file.";
    }
}
