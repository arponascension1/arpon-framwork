<?php

namespace Arpon\Mail;


use PHPMailer\PHPMailer\Exception;

class MailManager
{
    protected $app;
    protected array $to = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function to($address, $name = null): static
    {
        $this->to[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function send($view, array $data = [], $callback = null): void
    {
        $mailer = $this->driver(); // This returns PHPMailerMailer instance

        // Create a Message object, passing the internal PHPMailer instance
        $message = new Message($mailer->getPHPMailerInstance());

        $viewContent = '';
        $subject = '';

        if (is_string($view)) {
            if (str_starts_with(trim($view), '<')) {
                $viewContent = $view; // Raw HTML
            } else {
                // It's a view name, render it
                if (isset($this->app['view'])) {
                    $viewContent = $this->app['view']->make($view, $data)->render();
                } else {
                    throw new \RuntimeException('View service not available to render email view.');
                }
            }
        } elseif (is_object($view) && method_exists($view, 'build') && method_exists($view, 'render')) {
            // If $view is a Mailable object
            $view->build($message);
            $viewContent = $view->render();
            $subject = $view->subject; // Assuming Mailable has a subject property
            $message->subject($subject);
        } else {
            throw new \InvalidArgumentException('Invalid view type provided to MailManager::send().');
        }

        // Set the HTML content on the Message object
        $message->html($viewContent);

        // Execute the callback to allow user to configure the message (to, subject, etc.)
        if ($callback) {
            call_user_func($callback, $message);
        }

        // Get subject and recipients from the Message object
        $finalSubject = $message->getSubject();
        $finalRecipients = $message->getTo();

        // If no recipients were set via callback, but were set via MailManager->to()
        if (empty($finalRecipients) && !empty($this->to)) {
            $finalRecipients = $this->to;
        }

        // Iterate over recipients and send email for each
        foreach ($finalRecipients as $recipient) {
            $mailer->send($viewContent, $finalSubject, $recipient);
        }

        $this->to = []; // Clear recipients after sending
    }

    /**
     * @throws Exception
     */
    public function driver($driver = null): \Arpon\Mail\PHPMailerMailer
    {
        $config = $this->app['config']->get('mail');
        $config['from'] = $config['from'] ?? $this->app['config']->get('mail.from');
        return new PHPMailerMailer($this->app, $config);
    }
}
