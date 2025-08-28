<?php

namespace Arpon\Validation\Rules;

use Arpon\Http\File\UploadedFile;

class Image
{
    public function validate($attribute, $value, $parameters, $data): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        return str_starts_with($value->getMimeType(), 'image/');
    }

    public function message($attribute, $value, $parameters, $data): string
    {
        return "The {$attribute} must be an image.";
    }
}