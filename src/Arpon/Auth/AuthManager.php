<?php

namespace Arpon\Auth;

use Arpon\Config\Repository;
use Arpon\Contracts\Auth\UserProvider;
use Arpon\Contracts\Hashing\Hasher;
use Arpon\Database\ORM\Model;
use Arpon\Http\Request;
use Arpon\Session\SessionManager;
use InvalidArgumentException;
use Random\RandomException;

class AuthManager
{
    
    protected SessionManager $session;
    protected Hasher $hasher;
    protected Request $request;
    protected Repository $config;
    protected array $guards = [];
    protected array $providers = [];

    public function __construct(Repository $config, SessionManager $session, Hasher $hasher, Request $request)
    {
        $this->config = $config;
        $this->session = $session;
        $this->hasher = $hasher;
        $this->request = $request;
    }

    public function guard(?string $name = null)
    {
        $name = $name ?: $this->getDefaultGuard();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->resolveGuard($name);
        }

        return $this->guards[$name];
    }

    protected function resolveGuard(string $name)
    {
        $config = $this->config->get('auth.guards.' . $name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException("Auth guard driver [{$config['driver']}] is not supported.");
    }

    /**
     * @throws RandomException
     */
    protected function createSessionDriver(string $name, array $config): SessionGuard
    {
        $provider = $this->createUserProvider($config['provider']);

        return new SessionGuard($name, $provider, $this->session, $this->request, $this->config, $this->hasher);
    }

    public function createUserProvider(string $providerName): UserProvider
    {
        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->resolveUserProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    protected function resolveUserProvider(string $name): UserProvider
    {
        $config = $this->config->get('auth.providers.' . $name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth user provider [{$name}] is not defined.");
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Provider';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Auth user provider driver [{$config['driver']}] is not supported.");
    }

    protected function createEloquentProvider(array $config): UserProvider
    {
        return new EloquentUserProvider($this->hasher, $config['model']);
    }

    public function getDefaultGuard(): string
    {
        return $this->config->get('auth.defaults.guard');
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        return $this->guard()->attempt($credentials, $remember);
    }

    public function login(Model $user, bool $remember = false): void
    {
        $this->guard()->login($user, $remember);
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function user(): ?Model
    {
        return $this->guard()->user();
    }

    public function check(): bool
    {
        return $this->guard()->check();
    }

    public function guest(): bool
    {
        return !$this->guard()->guest();
    }

    public function id(): ?int
    {
        return $this->guard()->id();
    }

    public function userModel(): string
    {
        $providerName = $this->config->get('auth.guards.' . $this->getDefaultGuard() . '.provider');
        return $this->config->get('auth.providers.' . $providerName . '.model');
    }
}