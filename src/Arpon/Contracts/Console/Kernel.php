<?php

namespace Arpon\Contracts\Console;


interface Kernel
{

    public function handle($input, $output);

    public function call($command, array $parameters = [], $outputBuffer = null);

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string;
}