<?php

namespace Arpon\Support;

class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Arpon\Application
     */
    protected $app;

    /**
     * Create a new service provider instance.
     *
     * @param  \Arpon\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}