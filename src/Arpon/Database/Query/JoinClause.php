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
        $this->onClauses[] = ['type' => 'Basic', 'column' => $first, 'operator' => $operator, 'value' => $second, 'boolean' => $boolean, 'isJoinCondition' => true];
        // Add bindings for ON conditions to the *parent query's* 'join' bindings
        if(!($second instanceof Expression) && !\str_contains($second, '.') && !\is_numeric($second) && $operator !== 'IS' && $operator !== 'IS NOT'){
            // Heuristic: if $second is not an expression, not a column (contains '.'), not a number, and operator isn't IS (NULL)
            $this->parentQuery->addBinding($second, 'join');
        }
        return $this;
    }
    public function orOn(string $first, string $operator, string $second): static
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    public function getBindings(): array
    {
        // Bindings are now directly added to the parent query by the on() method.
        // This method could be removed or return an empty array if JoinClause itself no longer holds bindings.
        // For now, to align with how it was called:
        $joinBindings = [];
        foreach ($this->onClauses as $clause) {
            if (isset($clause['value']) && !($clause['value'] instanceof Expression) &&
                !$this->parentQuery->grammar->isColumn($clause['value'])) { // Assuming grammar has an isColumn helper
                $joinBindings[] = $clause['value'];
            }
        }
        return $joinBindings; // This might be redundant if on() adds to parent
    }

    public function getOnClauses(): array
    {
        return $this->onClauses;
    }
}