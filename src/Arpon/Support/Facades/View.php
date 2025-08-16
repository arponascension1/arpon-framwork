<?php

// src/Arpon/Support/Facades/View.php

namespace Arpon\Support\Facades;

class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}
