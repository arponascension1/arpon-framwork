<?php

namespace Arpon\Support\Facades;

use Arpon\Auth\SessionGuard;
use Arpon\Contracts\Auth\Authenticatable;
use Arpon\Database\ORM\Model;

/**
 * @method static bool attempt(array $credentials, bool $remember = false)
 * @method static void login(Authenticatable $user, bool $remember = false)
 * @method static void logout()
 * @method static Model|null user()
 * @method static bool check()
 * @method static bool guest()
 * @method static int|null id()
 * @method static string userModel()
 * @method static SessionGuard guard(string|null $name = null)
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }
}
