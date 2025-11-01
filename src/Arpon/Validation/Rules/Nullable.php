<?php

namespace Arpon\Validation\Rules;

class Nullable
{
    public function validate($attribute, $value, $parameters, $data): bool
    {
        return true;
    }

    public function message($attribute, $value, $parameters, $data): string
    {
        return "";
    }
}
