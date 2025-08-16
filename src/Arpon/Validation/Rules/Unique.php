<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ValidationRule;
use Arpon\Support\Facades\DB;


class Unique implements ValidationRule
{
    protected string $table;
    protected string $column;
    protected ?string $ignoreValue = null;
    protected ?string $ignoreColumn = null;

    public function validate(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        $this->table = $parameters[0] ?? '';
        $this->column = $parameters[1] ?? $attribute;
        $this->ignoreValue = $parameters[2] ?? null;
        $this->ignoreColumn = $parameters[3] ?? null;

        if (empty($this->table)) {
            throw new \InvalidArgumentException("Unique rule requires a table name.");
        }

        $query = DB::table($this->table)->where($this->column, $value);

        if (!is_null($this->ignoreValue)) {
            $query->where($this->ignoreColumn ?? 'id', '!=', $this->ignoreValue);
        }

        return !$query->first();
    }

    public function message(string $attribute, mixed $value, array $parameters, array $data): string
    {
        return "The {$attribute} has already been taken.";
    }
}
