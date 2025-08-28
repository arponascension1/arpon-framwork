<?php

namespace Arpon\Validation\Rules;

use Arpon\Http\File\UploadedFile;

class Max
{
    public function validate($attribute, $value, $parameters, $data): bool
    {
        if (empty($parameters[0])) {
            return false;
        }

        $max = $parameters[0];

        if ($value instanceof UploadedFile) {
            return ($value->getSize() / 1024) <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if(is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    public function message($attribute, $value, $parameters, $data): string
    {
        $max = $parameters[0];

        $type = 'characters';
        if ($value instanceof UploadedFile) {
            return "The {$attribute} may not be greater than {$max} kilobytes.";
        } elseif (is_array($value)) {
            $type = 'items';
        } elseif (is_numeric($value)) {
            return "The {$attribute} may not be greater than {$max}.";
        }

        return "The {$attribute} may not have more than {$max} {$type}.";
    }
}