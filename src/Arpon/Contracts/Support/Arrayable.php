<?php

namespace Arpon\Contracts\Support;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array;
}
