<?php

namespace Arpon\Support;

use Arpon\Contracts\Support\Arrayable as ArrayableContract;

class ArrayableObject implements ArrayableContract
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
