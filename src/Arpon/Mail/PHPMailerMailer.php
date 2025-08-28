<?php

namespace Arpon\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class PHPMailerMailer extends Mailer
{
    protected PHPMailer $phpmailer;

    /**
     * @throws Exception
     */
    public function __construct($app, $config)
    {
        parent::__construct($app, $config);

        $this->phpmailer = new PHPMailer(true); // true enables exceptions
        $this->configurePHPMailer($config);
    }

    /**
     * @throws Exception
     */
    protected function configurePHPMailer($config): void
    {
        $this->phpmailer->isSMTP();
        $this->phpmailer->Host = $config['host'];
        $this->phpmailer->SMTPAuth = true;
        $this->phpmailer->Username = $config['username'];
        $this->phpmailer->Password = $config['password'];
        $this->phpmailer->SMTPSecure = $config['encryption'];
        $this->phpmailer->Port = $config['port'];

        

        if (!empty($config['from']['address'])) {
            $this->phpmailer->setFrom($config['from']['address'], $config['from']['name'] ?? '');
        } else {
            $globalFrom = $this->app['config']->get('mail.from');
            $this->phpmailer->setFrom($globalFrom['address'], $globalFrom['name'] ?? '');
        }

        $this->phpmailer->isHTML(true);
    }

    /**
     * @throws \Exception
     */
    public function getPHPMailerInstance(): PHPMailer
    {
        return $this->phpmailer;
    }

    /**
     * @throws \Exception
     */
    public function send(string $view, string $subject, array $to): void
    {
        try {
            // Clear previous addresses to avoid sending to old recipients
            $this->phpmailer->clearAddresses();
            $this->phpmailer->clearAttachments();
            $this->phpmailer->clearAllRecipients();
            $this->phpmailer->clearCustomHeaders();

            $this->phpmailer->addAddress($to['address'], $to['name'] ?? '');
            $this->phpmailer->Subject = $subject;
            $this->phpmailer->Body = $view;

            $this->phpmailer->send();
        } catch (Exception $e) {
            throw new \Exception("Message could not be sent. Mailer Error: {$this->phpmailer->ErrorInfo}");
        }
    }
}
