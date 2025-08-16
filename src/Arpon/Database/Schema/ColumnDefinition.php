<?php

namespace Arpon\Database\Schema;

class ColumnDefinition
{
    public array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function nullable(): static
    {
        $this->attributes['nullable'] = true;
        return $this;
    }

    public function unique(): static
    {
        $this->attributes['unique'] = true;
        return $this;
    }

    public function after(string $column): static
    {
        $this->attributes['after'] = $column;
        return $this;
    }

    public function index(): static
    {
        $this->attributes['index'] = true;
        return $this;
    }

    public function default($value): static
    {
        $this->attributes['default'] = $value;
        return $this;
    }

    public function primary(): static
    {
        $this->attributes['primary'] = true;
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->attributes['autoIncrement'] = true;
        return $this;
    }

    public function first(): static
    {
        $this->attributes['first'] = true;
        return $this;
    }

    public function change(): static
    {
        $this->attributes['change'] = true;
        return $this;
    }

    public function constrained(string $table = null, string $column = 'id'): static
    {
        if (is_null($table)) {
            // Assuming a str_plural helper function exists or will be implemented
            $table = str_plural(str_replace(['_id', '_uuid'], '', $this->attributes['name']));
        }

        $this->attributes['foreign'] = [
            'references' => $column,
            'on' => $table,
        ];

        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->attributes['onDelete'] = $action;
        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->attributes['onUpdate'] = $action;
        return $this;
    }
}
