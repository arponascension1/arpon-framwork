<?php

namespace Arpon\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class PHPMailerMailer extends Mailer
{
    protected $phpmailer;

    public function __construct($app, $config)
    {
        parent::__construct($app, $config);

        $this->phpmailer = new PHPMailer(true); // true enables exceptions
        $this->configurePHPMailer($config);
    }

    protected function configurePHPMailer($config)
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

    public function send($view, $subject, $to)
    {
        try {
            $this->phpmailer->addAddress($to['address'], $to['name'] ?? '');
            $this->phpmailer->Subject = $subject;
            $this->phpmailer->Body = $view;

            $this->phpmailer->send();
        } catch (Exception $e) {
            throw new \Exception("Message could not be sent. Mailer Error: {$this->phpmailer->ErrorInfo}");
        }
    }
}
