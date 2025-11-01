<?php

namespace Arpon\Validation\Rules;

use Arpon\Http\File\UploadedFile;

class Mimes
{
    public function validate($attribute, $value, $parameters, $data): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        return in_array(strtolower($value->getExtension()), $parameters);
    }

    public function message($attribute, $value, $parameters, $data): string
    {
        return "The {$attribute} must be a file of type: " . implode(', ', $parameters);
    }
}