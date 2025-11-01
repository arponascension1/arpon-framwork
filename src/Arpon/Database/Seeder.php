<?php

namespace Arpon\Database;

abstract class Seeder
{
    public function __invoke()
    {
        $this->run();
    }

    abstract public function run(): void;

    protected function call(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            (new $seeder())();
        }
    }
}
