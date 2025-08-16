<?php

namespace Arpon\Database\Schema;

class ForeignKeyDefinition
{
    protected Blueprint $blueprint;
    protected string $column;
    protected string $references;
    protected string $on;
    protected ?string $onDelete = null;
    protected ?string $onUpdate = null;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): static
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): static
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function toSql(): array
    {
        // This will be compiled by the grammar
        return [
            'type' => 'foreign',
            'column' => $this->column,
            'references' => $this->references,
            'on' => $this->on,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate,
        ];
    }
}