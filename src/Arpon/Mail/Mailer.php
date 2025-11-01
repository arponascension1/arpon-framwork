<?php

namespace Arpon\Mail;

use Arpon\Foundation\Application;

class Mailer
{
    /**
     * The global "from" address and name.
     *
     * @var array
     */
    protected mixed $from;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new Mailer instance.
     *
     * @param Application $app
     * @param array $config
     * @return void
     */
    public function __construct(Application $app, array $config)
    {
        $this->app = $app;

        $this->from = $config['from'];
    }

    /**
     * Send a new message.
     *
     * @param string $view
     * @param string $subject
     * @param array $to
     * @return void
     */
    public function send(string $view, string $subject, array $to)
    {
        // This method will be overridden by concrete mailer implementations
        // like PHPMailerMailer.
    }
}