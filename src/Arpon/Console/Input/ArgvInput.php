<?php

namespace Arpon\Console\Input;

class ArgvInput
{
    private array $tokens;

    public function __construct(array $argv = null)
    {
        $this->tokens = $argv ?? $_SERVER['argv'];
        // Slice off the script name
        array_shift($this->tokens);
    }

    public function getFirstArgument(): ?string
    {
        return $this->tokens[0] ?? null;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }
}
