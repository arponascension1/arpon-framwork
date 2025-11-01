<?php

namespace Arpon\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class Message
{
    protected PHPMailer $mailer;
    protected string $subject = '';
    protected array $to = [];

    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function to($address, $name = null)
    {
        $this->to[] = ['address' => $address, 'name' => $name];
        // Do not add address to PHPMailer here, as PHPMailerMailer will handle it per recipient.
        return $this;
    }

    public function subject(string $subject)
    {
        $this->subject = $subject;
        // Do not set subject on PHPMailer here, as PHPMailerMailer will handle it.
        return $this;
    }

    public function html(string $html)
    {
        // Do not set body on PHPMailer here, as PHPMailerMailer will handle it.
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTo(): array
    {
        return $this->to;
    }
}