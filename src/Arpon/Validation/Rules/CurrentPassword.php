<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Support\Facades\Hash;
use Arpon\Support\Facades\Auth;

class CurrentPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Hash::check($value, Auth::user()->password);
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return 'The :attribute is incorrect.';
    }
}
