<?php

namespace Arpon\Validation\Rules;

class Boolean
{
    public function validate($attribute, $value, $parameters, $data): bool
    {
        return is_bool($value);
    }

    public function message($attribute, $value, $parameters, $data): string
    {
        return "The {$attribute} field must be true or false.";
    }
}
