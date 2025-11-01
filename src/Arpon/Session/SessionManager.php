<?php

namespace Arpon\Session;

use Arpon\Config\Repository as Config;
use Random\RandomException;

class SessionManager
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->start();
    }

    protected function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            $lifetime = $this->config->get('session.lifetime', 120) * 60;
            ini_set('session.gc_maxlifetime', $lifetime);
            session_set_cookie_params($lifetime);
            session_start();
        }
    }

    public function startSession(): void
    {
        $_SESSION['__flash'] = $_SESSION['__flash_next'] ?? [];
        $_SESSION['__flash_next'] = [];

        $_SESSION['_old_input'] = $_SESSION['_old_input_next'] ?? [];
        $_SESSION['_old_input_next'] = [];

        // Store the current URL as the previous URL for the next request
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->put('_previous_url', $_SERVER['HTTP_REFERER']);
        } else {
            $this->put('_previous_url', '/'); // Default to root if no referer
        }
    }

    public function saveSession(): void
    {
        // Clear data from __flash (consumed in current request)
        if (isset($_SESSION['__flash']) && is_array($_SESSION['__flash'])) {
            foreach ($_SESSION['__flash'] as $key) {
                $this->forget($key);
            }
        }
        $_SESSION['__flash'] = []; // Ensure __flash is empty after consumption

        // Old input is handled separately, no need to clear here.
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        session_unset();
        session_destroy();
        session_start(); // Restart session after destroying
    }

    public function regenerate(bool $destroy = false): string
    {
        if ($destroy) {
            session_destroy();
        }
        session_regenerate_id($destroy);
        return session_id();
    }

    /**
     * @throws RandomException
     */
    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->put('_token', bin2hex(random_bytes(16)));
        }
        return $this->get('_token');
    }

    public function invalidate(): void
    {
        $this->flush();
    }

    /**
     * @throws RandomException
     */
    public function regenerateToken(): void
    {
        $this->put('_token', bin2hex(random_bytes(16)));
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $_SESSION['__flash_next'][] = $key;
    }

    public function flashInput(array $input): void
    {
        $_SESSION['_old_input_next'] = $input;
    }

    public function getOldInput(string $key, mixed $default = null): mixed
    {
        return $this->get('_old_input')[$key] ?? $default;
    }

    public function reflash(): void
    {
        foreach ($_SESSION['__flash'] as $key) {
            $_SESSION['__flash_next'][] = $key;
        }
    }

    public function keep(array|string $keys): void
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            $_SESSION['__flash_next'][] = $key;
        }
    }

    public function clearFlashedData(): void
    {
        // This method is no longer strictly needed as saveSession handles clearing.
        // It's kept for now to avoid breaking existing calls if any.
    }

    public function previousUrl(): string
    {
        return $this->get('_previous_url', '/');
    }

    public function getAll(): array
    {
        return $_SESSION;
    }
}