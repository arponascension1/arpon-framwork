<?php

namespace Arpon\Mail;

class MailManager
{
    /**
     * The application instance.
     *
     * @var \Arpon\Application
     */
    protected $app;

    /**
     * Create a new mail manager instance.
     *
     * @param  \Arpon\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a driver instance.
     *
     * @param  string|null  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $config = $this->app['config']->get('mail');
        $config['from'] = $config['from'] ?? $this->app['config']->get('mail.from');
        return new PHPMailerMailer($this->app, $config);
    }
}
