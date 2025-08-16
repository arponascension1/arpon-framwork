<?php

namespace Arpon\Contracts\Container;

use Closure;
use Throwable;

interface Container
{
    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool;

    /**
     * Register a binding with the container.
     *
     * @param array|string $abstract
     * @param string|Closure|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(array|string $abstract, string|Closure $concrete = null, bool $shared = false): void;

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bindIf(string $abstract, string|Closure $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     *
     * @param array|string $abstract
     * @param string|Closure|null $concrete
     * @return void
     */
    public function singleton(array|string $abstract, string|Closure $concrete = null): void;

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return void
     */
    public function singletonIf(string $abstract, string|Closure $concrete = null): void;

    /**
     * "Extend" an abstract type in the container.
     *
     * @param string $abstract
     * @param Closure $extend
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend(string $abstract, Closure $extend): void;

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance): mixed;

    /**
     * Assign a set of tags to a given binding.
     *
     * @param array|string $abstracts
     * @param  array|mixed  $tags
     * @return void
     */
    public function tag(array|string $abstracts, mixed $tags): void;

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param string $tag
     * @return iterable
     */
    public function tagged(string $tag): iterable;

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Resolve the given abstract type from the container.
     *
     * @param string $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given Closure / class method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param  array  $parameters
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function call(callable|string $callback, array $parameters = [], string $defaultMethod = null): mixed;

    /**
     * Get a closure to resolve a buildable class out of the container.
     *
     * @param string $abstract
     * @return Closure
     */
    public function factory(string $abstract): Closure;

    /**
     * An alias for the "make" method.
     *
     * @param string $abstract
     * @return mixed
     */
    public function offsetGet(string $abstract): mixed;

    /**
     * Determine if a given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists(string $offset): bool;

    /**
     * Set the given offset.
     *
     * @param string $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(string $offset, mixed $value): void;

    /**
     * Unset the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset(string $offset): void;
}