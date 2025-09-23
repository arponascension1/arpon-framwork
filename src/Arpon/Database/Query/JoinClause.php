<?php

namespace Arpon\Database\Query;

/**
 * Represents a Join clause in a query.
 */
class JoinClause { // Does not need to extend Builder
    protected Builder $parentQuery; // Store parent query to add bindings
    public string $type;
    public string $table;
    public array $onClauses = [];

    public function __construct(Builder $parentQuery, string $type, string $table)
    {
        $this->parentQuery = $parentQuery;
        $this->type = $type;
        $this->table = $table;
    }

    public function on(string $first, string $operator, string $second, string $boolean = 'AND'): static
    {
        if ($this->parentQuery->grammar->isColumn($second)) {
            $this->onClauses[] = ['type' => 'Column', 'first' => $first, 'operator' => $operator, 'second' => $second, 'boolean' => $boolean];
        } else {
            $this->onClauses[] = ['type' => 'Basic', 'column' => $first, 'operator' => $operator, 'value' => $second, 'boolean' => $boolean];
            $this->parentQuery->addBinding($second, 'join');
        }
        return $this;
    }

    public function orOn(string $first, string $operator, string $second): static
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    public function getOnClauses(): array
    {
        return $this->onClauses;
    }
}