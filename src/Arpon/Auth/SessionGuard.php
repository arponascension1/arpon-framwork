<?php

namespace Arpon\Auth;

use Arpon\Config\Repository;
use Arpon\Contracts\Auth\Authenticatable;
use Arpon\Contracts\Auth\Guard;
use Arpon\Contracts\Auth\UserProvider;
use Arpon\Contracts\Hashing\Hasher;
use Arpon\Http\Request;
use Arpon\Session\SessionManager;
use Random\RandomException;

class SessionGuard implements Guard
{
    protected string $name;
    protected UserProvider $provider;
    protected SessionManager $session;
    protected ?Authenticatable $user = null;
    protected Request $request;
    protected Repository $config;
    protected Hasher $hasher;

    /**
     * @throws RandomException
     */
    public function __construct(string $name, UserProvider $provider, SessionManager $session, Request $request, Repository $config, Hasher $hasher)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->session = $session;
        $this->request = $request;
        $this->config = $config;
        $this->hasher = $hasher;

        $this->user = $this->userFromSession();

        if (is_null($this->user)) {
            $this->user = $this->userFromCookie();
        }
    }

    protected function userFromSession()
    {
        if ($this->session->has($this->getName())) {
            return $this->provider->retrieveById($this->session->get($this->getName()));
        }
    }

    /**
     * @throws RandomException
     */
    protected function userFromCookie()
    {
        $cookie = $this->request->cookie($this->getRememberName());

        if ($cookie && str_contains($cookie, '|')) {
            [$userId, $token] = explode('|', $cookie, 2);

            if ($user = $this->provider->retrieveByToken($userId, $token)) {
                $this->login($user);
                return $user;
            }
        }
    }

    public function check(): bool
    {
        return !is_null($this->user());
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        return $this->user;
    }

    public function id(): mixed
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            return true;
        }

        return false;
    }

    /**
     * @throws RandomException
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        return false;
    }

    /**
     * @throws RandomException
     */
    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->session->put($this->getName(), $user->getAuthIdentifier());

        if ($remember) {
            $this->rememberUser($user);
        }

        $this->user = $user;

        $this->session->regenerate();
    }

    /**
     * @throws RandomException
     */
    protected function rememberUser(Authenticatable $user): void
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = $this->hasher->make($token);
        $this->provider->updateRememberToken($user, $hashedToken);
        $cookieValue = $user->getAuthIdentifier() . '|' . $token;
        $lifetime = $this->config->get('session.remember', 30) * 60 * 24;
        setcookie($this->getRememberName(), $cookieValue, time() + $lifetime, '/');
    }

    public function logout(): void
    {
        $user = $this->user();

        $this->user = null;

        $this->session->forget($this->getName());

        $this->session->invalidate();

        $this->session->regenerateToken();

        if ($user) {
            $this->provider->updateRememberToken($user, null);
        }

        setcookie($this->getRememberName(), '', time() - 3600, '/');
    }

    public function getName(): string
    {
        return 'login_' . $this->name . '_' . sha1(static::class);
    }

    public function getRememberName(): string
    {
        return 'remember_' . $this->name . '_' . sha1(static::class);
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }
}
