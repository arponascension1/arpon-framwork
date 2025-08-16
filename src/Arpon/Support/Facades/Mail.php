<?php

namespace Arpon\Support\Facades;

/**
 * @method static void send(string $view, string $subject, array $to)
 *
 * @see \Arpon\Mail\PHPMailerMailer
 */
class Mail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mailer';
    }
}
