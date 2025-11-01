<?php



namespace Arpon\Foundation;

use Arpon\Filesystem\FilesystemServiceProvider;
use Closure;
use Exception;
use Arpon\Container\Container;
use Arpon\Contracts\Http\Kernel;
use Arpon\Routing\RoutingServiceProvider;
use Arpon\Support\ServiceProvider;
use Arpon\View\ViewServiceProvider;
use Arpon\Console\Input\ArgvInput;
use Arpon\Contracts\Console\Kernel as ConsoleKernel;

/**
 * The main application class.
 */
class Application extends Container implements \Arpon\Contracts\Foundation\Application
{
    protected string $basePath;
    protected array $serviceProviders = [];
    protected array $bootedProviders = [];
    protected array $bootedCallbacks = [];

    /**
     * The core service providers.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * The core service providers.
     *
     * @var array
     */
    protected array $coreProviders = [
        \Arpon\Auth\AuthServiceProvider::class,
        \Arpon\Hashing\HashServiceProvider::class,
        \Arpon\Mail\MailServiceProvider::class,
        \Arpon\Routing\RoutingServiceProvider::class,
        \Arpon\Session\SessionServiceProvider::class,
        \Arpon\View\ViewServiceProvider::class,
        \Arpon\Log\LogServiceProvider::class,
        \Arpon\Routing\RouteServiceProvider::class,
        \Arpon\Database\DatabaseServiceProvider::class,
        \Arpon\Filesystem\StorageServiceProvider::class,
        FilesystemServiceProvider::class
    ];

    /**
     * The core class aliases.
     *
     * @var array
     */
    protected array $coreAliases = [
        'Mail' => \Arpon\Support\Facades\Mail::class,
    ];

    /**
     * @throws Exception
     */
    public function __construct(string|null $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        static::setInstance($this);

        $this->registerBaseBindings();
    }

    /**
     * @throws Exception
     */
    public static function configure($basePath): object
    {
        $app = new static(dirname($basePath));

        (new \Arpon\Foundation\Bootstrap\LoadEnvironmentVariables)->bootstrap($app);
        (new \Arpon\Foundation\Bootstrap\LoadConfiguration)->bootstrap($app);

        $app->registerFacades();
        $app->instance('router', new \Arpon\Routing\Router($app));

        return new ApplicationBuilder($app);
    }


    /**
     * @throws Exception
     */
    public function handleRequest($request): void
    {
        $this->boot();
        $kernel = $this->make(\Arpon\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $response->send();
    }

    /**
     * Register the core service providers.
     *
     * @return void
     */
    public function registerCoreProviders(): void
    {
        foreach ($this->coreProviders as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register the core class aliases.
     *
     * @return void
     */
    public function registerCoreAliases(): void
    {
        foreach ($this->coreAliases as $abstract => $alias) {
            $this->alias($abstract, $alias);
        }
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     * @throws Exception
     */
    public function registerConfiguredProviders(): void
    {
        $providers = $this->make('config')->get('app.providers', []);

        foreach ($providers as $provider) {
            $this->register($provider);
        }

        // Bind the HTTP Kernel interface to its concrete implementation
        $this->singleton(
            Kernel::class,
            function ($app) {
                return new \Arpon\Http\Kernel($app, $app->make('router'), new \Arpon\Foundation\Exceptions\Handler());
            }
        );
    }

    /**
     * Register a class alias.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$abstract] = $alias;

        class_alias($alias, $abstract);
    }

    public function register(string $provider): ServiceProvider
    {
        $providerInstance = new $provider($this);
        $providerInstance->register();
        $this->serviceProviders[] = $providerInstance;
        return $providerInstance;
    }

    public function boot(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }

        foreach ($this->bootedCallbacks as $callback) {
            $callback($this);
        }
    }

    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function bootstrapWith(array $bootstrappers): void
    {
        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

            (new $bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
        }
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->bindPathsInContainer();
        return $this;
    }

    protected function bindPathsInContainer(): void
    {
        $this->singleton('path', fn() => $this->basePath);
        $this->singleton('path.base', fn() => $this->basePath);
    }

    protected function registerBaseBindings(): void
    {
        $this->singleton('app', fn () => $this);
        $this->singleton(Container::class, fn () => $this);
        $this->singleton('events', fn ($app) => new \Arpon\Events\Dispatcher($app));
    }

    /**
     * Register the basic service providers.
     *
     * @return void
     */
            

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases(): void
    {
        $aliases = [
            'app' => [\Arpon\Foundation\Application::class, \Arpon\Contracts\Container\Container::class],
            'Route' => [\Arpon\Support\Facades\Route::class],
            'View' => [\Arpon\Support\Facades\View::class],
            'DB' => [\Arpon\Support\Facades\DB::class],
            'Session' => [\Arpon\Support\Facades\Session::class],
            'Auth' => [\Arpon\Support\Facades\Auth::class],
            'Hash' => [\Arpon\Support\Facades\Hash::class],
            'Schema' => [\Arpon\Support\Facades\Schema::class],
        ];

        foreach ($aliases as $key => $aliasGroup) {
            foreach ($aliasGroup as $alias) {
                $this->aliases[$alias] = $key;
            }
        }
    }

    public function basePath($path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    // Add dummy implementations for missing methods

    public function version(): string
    {
        return '1.0.0';
    }

    public function path(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    public function configPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config';
    }

    public function databasePath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'database';
    }

    public function langPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
    }

    public function publicPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public';
    }

    public function storagePath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage';
    }

    public function resourcePath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources';
    }

    public function bootstrapPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap';
    }

    public function environmentPath(): string
    {
        return $this->basePath;
    }

    public function environmentFile(): string
    {
        return '.env';
    }

    public function fullEnvironmentFilePath(): string
    {
        return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
    }

    public function environment($environments = null): string
    {
        // In a real application, you would get this from the .env file
        return 'production';
    }

    public function isLocal(): bool
    {
        return $this->environment() === 'local';
    }

    public function isProduction()
    {
        return $this->environment() === 'production';
    }

    public function runningInConsole()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    public function runningUnitTests()
    {
        return false;
    }

    public function hasDebugMode()
    {
        // In a real application, you would get this from the config
        return false;
    }

    public function terminating(Closure $callback)
    {
        // Not implemented
        return $this;
    }

    public function terminate()
    {
        // Not implemented
    }

    public function getLoadedProviders()
    {
        return $this->serviceProviders;
    }

    public function getDeferredServices()
    {
        return [];
    }

    public function isDeferredService($service)
    {
        return false;
    }

    public function setDeferredServices(array $services)
    {
        // Not implemented
    }

    public function addDeferredService($service, $provider)
    {
        // Not implemented
    }

    public function hasBeenBootstrapped()
    {
        return true;
    }

    public function setLocale($locale)
    {
        // Not implemented
    }

    public function getLocale()
    {
        return 'en';
    }

    public function getFallbackLocale()
    {
        return 'en';
    }

    public function setFallbackLocale($fallbackLocale)
    {
        // Not implemented
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function down(\Arpon\Contracts\Foundation\Closure $callback)
    {
        // Not implemented
    }

    public function forgetDown()
    {
        // Not implemented
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet(mixed $abstract): mixed
    {
        return $this->make($abstract);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->singleton($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->aliases[$offset], $this->instances[$offset], $this->bindings[$offset]);
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || $this->isAlias($abstract);
    }

    public function resolved(string $abstract): bool
    {
        return isset($this->instances[$abstract]);
    }

    public function bind(array|string $abstract, string|Closure $concrete = null, bool $shared = false): void
    {
        // If no concrete implementation is provided, we'll assume the abstract is
        // the concrete implementation.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function bindIf(string $abstract, string|Closure|null $concrete = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    public function singleton(array|string $abstract, string|Closure $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function singletonIf(string $abstract, string|Closure|null $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    public function extend(string $abstract, Closure $extend): void
    {
        // Not implemented
    }

    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    public function tag(array|string $abstracts, mixed $tags): void
    {
        // Not implemented
    }

    public function tagged(string $tag): iterable
    {
        return [];
    }

    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {

    }

    public function factory(string $abstract): Closure
    {
        return fn() => $this->make($abstract);
    }

    protected function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    public function registerCoreBindings(): void
    {
        $this->singleton(
            \Arpon\Contracts\Console\Kernel::class,
            function ($app) {
                return new \App\Console\Kernel($app);
            }
        );

        $this->singleton(
            \Arpon\Contracts\Http\Kernel::class,
            function ($app) {
                return new \Arpon\Http\Kernel($app, $app->make('router'), new \Arpon\Foundation\Exceptions\Handler());
            }
        );

        $this->singleton(\Arpon\Database\Migrator::class, function ($app) {
            return new \Arpon\Database\Migrator($app->make('db'), $app);
        });

        $this->singleton(\Arpon\Security\Csrf::class, function ($app) {
            return new \Arpon\Security\Csrf();
        });
    }

    protected function registerFacades(): void
    {
        \Arpon\Support\Facades\Facade::setFacadeApplication($this);
    }

    /**
     * @throws Exception
     */
    public function handleCommand(ArgvInput $input)
    {
        $this->boot();

        $commandName = $input->getFirstArgument();
        $commandArgs = array_slice($input->getTokens(), 1);

        $kernel = $this->make(ConsoleKernel::class);
        $commands = $kernel->getCommands();

        $processedCommands = [];
        foreach ($commands as $key => $class) {
            if (is_int($key)) {
                $commandInstance = $this->make($class);
                $commandInstance->setApp($this);
                $signature = $commandInstance->getSignature();
                $name = explode(' ', $signature)[0];
                $processedCommands[$name] = $class;
            } else {
                $processedCommands[$key] = $class;
            }
        }

        if ($commandName && isset($processedCommands[$commandName])) {
            $commandClass = $processedCommands[$commandName];
            $command = $this->make($commandClass);
            $command->setApp($this);

            $parsedArgs = [];
            $parsedOptions = [];
            foreach ($commandArgs as $arg) {
                if (str_starts_with($arg, '--')) {
                    $parts = explode('=', substr($arg, 2), 2);
                    $parsedOptions[$parts[0]] = $parts[1] ?? true;
                } else {
                    $parsedArgs[] = $arg;
                }
            }

            return $command->handle($parsedArgs, $parsedOptions);
        }

        echo "Available commands:\n";
        foreach ($processedCommands as $name => $class) {
            $command = $this->make($class);
            $command->setApp($this);
            $description = $command->getDescription();
            echo "  {$name}: {$description}\n";
        }
        return 1;
    }
}
