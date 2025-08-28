<?php

namespace Arpon\Validation;

use Exception;

class ValidationException extends Exception
{
    protected ErrorBag $errors;

    public function __construct(ErrorBag $errors, string $message = "The given data was invalid.", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }
}
