<?php


namespace Arpon\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * A powerful IoC container.
 *
 * This class is a simplified implementation of Laravel's service container.
 * It manages class bindings and resolves dependencies automatically.
 */
class Container
{
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected static $instance;
    protected array $instances = [];

    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * Set the shared instance of the container.
     *
     * @param Container|null $container
     * @return Container|null
     */
    public static function setInstance(Container $container = null): ?static
    {
        return static::$instance = $container;
    }
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
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

    /**
     * Register a shared binding in the container (a "singleton").
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton(array|string $abstract, string|Closure $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * "Make" an instance of the given type.
     *
     * This is an alias for the `resolve` method.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Exception
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolve(string $abstract): mixed
    {
        // If an instance of the type is already shared, return it.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // If we don't have a binding for this type, we'll try to build it.
        if (!isset($this->bindings[$abstract])) {
            return $this->build($abstract);
        }

        $concrete = $this->bindings[$abstract]['concrete'];

        // If the concrete implementation is a Closure, we'll execute it and
        // get the result.
        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            // Otherwise, we'll resolve the concrete implementation.
            $object = $this->resolve($concrete);
        }

        // If the binding is marked as shared, we'll cache the instance.
        if (!empty($this->bindings[$abstract]['shared'])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @return mixed
     *
     * @throws \Exception
     */
    protected function build(string $concrete): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Exception $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        // If the class is not instantiable, we can't build it.
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there is no constructor, we can just create a new instance.
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        // Create a new instance of the class with the resolved dependencies.
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws Exception
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // If the dependency is a class, we will resolve it from the container.
            if ($parameter->isOptional()) {
                continue;
            }
            if ($parameter->getType() && !$parameter->getType()->isBuiltin()) {
                $dependencies[] = $this->resolve($parameter->getType()->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                // For built-in types, use the default value if available.
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new Exception("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
            }
        }

        return $dependencies;
    }
}
