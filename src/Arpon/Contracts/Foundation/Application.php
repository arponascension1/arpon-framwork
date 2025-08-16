<?php

namespace Arpon\Contracts\Foundation;

use Arpon\Contracts\Container\Container;

interface Application extends \ArrayAccess, Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the base path of the Laravel installation.
     *
     * @return string
     */
    public function basePath(): string;

    /**
     * Get the path to the application directory.
     *
     * @return string
     */
    public function path(): string;

    /**
     * Get the path to the configuration directory.
     *
     * @return string
     */
    public function configPath(): string;

    /**
     * Get the path to the database directory.
     *
     * @return string
     */
    public function databasePath(): string;

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function langPath(): string;

    /**
     * Get the path to the public directory.
     *
     * @return string
     */
    public function publicPath(): string;

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath(): string;

    /**
     * Get the path to the resources directory.
     *
     * @return string
     */
    public function resourcePath(): string;

    /**
     * Get the path to the bootstrap directory.
     *
     * @return string
     */
    public function bootstrapPath();

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath();

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile();

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function fullEnvironmentFilePath();

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  $environments
     * @return string|bool
     */
    public function environment($environments = null);

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal();

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction();

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole();

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests();

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugMode();

    /**
     * Register a terminating callback with the application.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function terminating(\Closure $callback);

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate();

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders();

    /**
     * Get the application's deferred services.
     *
     * @return array
     */
    public function getDeferredServices();

    /**
     * Get the application's deferred services.
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service);

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services);

    /**
     * Add a deferred service to the application.
     *
     * @param  string  $service
     * @param  string  $provider
     * @return void
     */
    public function addDeferredService($service, $provider);

    /**
     * Determine if the application has been bootstrapped.
     *
     * @return bool
     */
    public function hasBeenBootstrapped();

    /**
     * Set the application's locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale);

    /**
     * Get the application's locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Get the application's fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale();

    /**
     * Set the application's fallback locale.
     *
     * @param  string  $fallbackLocale
     * @return void
     */
    public function setFallbackLocale($fallbackLocale);

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance();

    /**
     * Register a maintenance mode callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function down(Closure $callback);

    /**
     * Unregister the maintenance mode callback.
     *
     * @return void
     */
    public function forgetDown();
}