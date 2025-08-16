<?php

namespace Arpon\Database\Query;

/**
 * Represents a raw SQL expression.
 */
class Expression {
    protected string $value;
    public function __construct(string $value) { $this->value = $value; }
    public function getValue(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}