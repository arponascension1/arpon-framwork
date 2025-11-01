<?php

namespace Arpon\Support\Facades;

/**
 * @method static void send(mixed $view, array $data = [], callable $callback = null)
 *
 * @see \Arpon\Mail\MailManager
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
