<?php

use Arpon\Auth\AuthManager;
use Arpon\Container\Container;
use Arpon\Http\Request;
use Arpon\Support\Facades\Facade;
use Arpon\Session\SessionManager;
use JetBrains\PhpStorm\NoReturn;

if (!function_exists('app')) {
    function app($abstract = null)
    {
        $container = Container::getInstance();
        if (is_null($abstract)) {
            return $container;
        }
        return $container->make($abstract);
    }
}

if (!function_exists('base_path')) {
    function base_path($path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }
        if (is_array($key)) {
            return app('config')->set($key);
        }
        return app('config')->get($key, $default);
    }
}

if (!function_exists('__')) {
    function __($key, $replace = [], $locale = null): array|string|null
    {
        return app('translator')->get($key, $replace, $locale);
    }
}

if (!function_exists('view')) {
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app('view');
        if (func_num_args() === 0) {
            return $factory;
        }
        return $factory->make($view, $data, $mergeData);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null): SessionManager|null
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * @return AuthManager
     */
    function auth(): AuthManager
    {
        return app('auth');
    }
}

if (!function_exists('back')) {
    function back($status = 302, $headers = [], $fallback = false): \Arpon\Http\RedirectResponse
    {
        return app('redirect')->back($status, $headers, $fallback);
    }
}

if (!function_exists('redirect')) {
    function redirect($to = null, $status = 302, $headers = [], $secure = null): \Arpon\Routing\Redirector|\Arpon\Http\RedirectResponse
    {
        if (is_null($to)) {
            return app('redirect');
        }

        return app('redirect')->to($to, $status, $headers, $secure);
    }
}

if (!function_exists('error')) {
    function error($key = null): string|\Arpon\Validation\ErrorBag|null
    {
        $errorBag = Arpon\View\View::getCurrent()->getErrorBag();

        if (is_null($key)) {
            return $errorBag;
        }

        if ($errorBag && $errorBag->has($key)) {
            return $errorBag->first($key);
        }

        return null;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        static $loaded = false;

        if (!$loaded) {
            $dotenv = Dotenv\Dotenv::createImmutable(base_path());
            $dotenv->load();
            $loaded = true;
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('layout')) {
    function layout($layout): void
    {
        Arpon\View\View::getCurrent()->setLayout($layout);
    }
}

if (!function_exists('section')) {
    function section($name, $content = null): void
    {
        Arpon\View\View::getCurrent()->section($name, $content);
    }
}

if (!function_exists('endSection')) {
    function endSection(): void
    {
        Arpon\View\View::getCurrent()->endSection();
    }
}

if (!function_exists('yieldSection')) {
    function yieldSection($name, $default = ''): void
    {
        echo Arpon\View\View::getCurrent()->yield($name, $default);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        return app('url')->route($name, $parameters, true);
    }
}

if (!function_exists('old')) {
    function old($key = null, $default = null)
    {
        return app('session')->getOldInput($key, $default);
    }
}

if (!function_exists('partial')) {
    function partial($view, $data = []): void
    {
        echo app('view')->makePartial($view, $data)->render();
    }
}

if (!function_exists('dd')) {
    #[NoReturn]
    function dd(...$args): void
    {
        echo '<style>body { margin: 0; }</style>';
        echo '<pre style="background-color: #333; color: #0f0; padding: 10px; margin: 0; font-family: monospace; white-space: pre-wrap; word-wrap: break-word;">';
        foreach ($args as $x) {
            var_dump($x);
        }
        echo '</pre>';
        die(1);
    }
}

if (!function_exists('str_plural')) {
    function str_plural(string $value): string
    {
        if (str_ends_with($value, 's')) {
            return $value;
        }
        return $value . 's';
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        $baseUrl = env('APP_URL', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}
