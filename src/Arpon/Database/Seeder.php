<?php

namespace Arpon\Database;

abstract class Seeder
{
    public function __invoke(): void
    {
        $this->run();
    }

    abstract public function run();
}