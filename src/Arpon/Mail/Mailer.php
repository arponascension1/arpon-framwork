<?php

namespace Arpon\Mail;

class Mailer
{
    /**
     * The transport type.
     *
     * @var string
     */
    protected $transport;

    /**
     * The global "from" address and name.
     *
     * @var array
     */
    protected $from;

    /**
     * The application instance.
     *
     * @var \Arpon\Application
     */
    protected $app;

    /**
     * Create a new Mailer instance.
     *
     * @param  \Arpon\Application  $app
     * @param  array  $config
     * @return void
     */
    public function __construct($app, $config)
    {
        $this->app = $app;
        $this->transport = $config['transport'];
        $this->from = $config['from'];
    }

    /**
     * Send a new message.
     *
     * @param  string  $view
     * @param  string  $subject
     * @param  array  $to
     * @return void
     */
    public function send($view, $subject, $to)
    {
        // This method will be overridden by concrete mailer implementations
        // like PHPMailerMailer.
    }
}