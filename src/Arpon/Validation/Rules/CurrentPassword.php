<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Auth\Authenticatable;
use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Support\Facades\Auth;
use Arpon\Support\Facades\Hash;

class CurrentPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        $guard = $parameters[0] ?? null;
        $userId = $parameters[1] ?? null;

        $user = $this->getUser($guard, $userId);

        if (!$user) {
            return false;
        }

        return Hash::check($value, $user->getAuthPassword());
    }

    protected function getUser($guard, $userId): \Arpon\Database\ORM\Model
    {
        // If only one parameter is passed and it's numeric, treat it as a user ID and use the default guard.
        if (is_numeric($guard) && is_null($userId)) {
            $userId = (int)$guard;
            $guard = null;
        }

        $guard = Auth::guard($guard);

        if ($userId) {
            $provider = $guard->getProvider();
            return $provider->retrieveById($userId);
        }

        return $guard->user();
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return 'The :attribute is incorrect.';
    }
}