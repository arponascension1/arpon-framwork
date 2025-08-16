<?php

namespace Arpon\Database\Query\Grammars;

use Arpon\Database\Query\Builder as QueryBuilder;
use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\ColumnDefinition;

class SQLiteGrammar extends Grammar
{
    /**
     * Wrap a value in keyword identifiers. SQLite uses double quotes by default.
     */
    protected function wrapValueSegment(string $segment): string
    {
        return ($segment === '*') ? $segment : '"' . \str_replace('"', '""', $segment) . '"';
    }

    /**
     * Compile an update statement into SQL.
     * SQLite does not support ORDER BY or LIMIT clauses directly in UPDATE statements.
     */
    public function compileUpdate(QueryBuilder $query, array $values): string
    {
        $originalOrders = $query->orders;
        $originalLimit = $query->limit;
        $query->orders = null; // SQLite does not support ORDER BY in UPDATE directly
        $query->limit = null;  // SQLite does not support LIMIT in UPDATE directly

        $sql = parent::compileUpdate($query, $values);

        $query->orders = $originalOrders;
        $query->limit = $originalLimit;

        return $sql;
    }

    /**
     * Compile a delete statement into SQL.
     * SQLite does not support ORDER BY or LIMIT clauses directly in DELETE statements.
     */
    public function compileDelete(QueryBuilder $query): string
    {
        $originalOrders = $query->orders;
        $originalLimit = $query->limit;
        $query->orders = null;
        $query->limit = null;

        $sql = parent::compileDelete($query);

        $query->orders = $originalOrders;
        $query->limit = $originalLimit;

        return $sql;
    }

    /**
     * Compile a "truncate table" statement for SQLite.
     */
    public function compileTruncate(QueryBuilder $query): array
    {
        return [
            'DELETE FROM ' . $this->wrapTable($query->from) => [],
            // Resetting autoincrement requires a separate statement for sqlite_sequence table
            // 'DELETE FROM sqlite_sequence WHERE name = ' . $this->wrap($query->from) => [],
            // For simplicity, just the DELETE. User can VACUUM or handle sequence separately if needed.
        ];
    }

    // --- SQLite Specific Date/Time Where Compilers using strftime ---
    protected function compileWhereDate(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // SQLite: strftime('%Y-%m-%d', column)
        return "strftime('%Y-%m-%d', " . $this->wrap($where['column']) . ") " . $where['operator'] . " " . $value;
    }
    protected function compileWhereTime(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // SQLite: strftime('%H:%M:%S', column)
        return "strftime('%H:%M:%S', " . $this->wrap($where['column']) . ") " . $where['operator'] . " " . $value;
    }
    protected function compileWhereDay(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // SQLite: strftime('%d', column)
        return "CAST(strftime('%d', " . $this->wrap($where['column']) . ") AS INTEGER) " . $where['operator'] . " " . $value;
    }
    protected function compileWhereMonth(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // SQLite: strftime('%m', column)
        return "CAST(strftime('%m', " . $this->wrap($where['column']) . ") AS INTEGER) " . $where['operator'] . " " . $value;
    }
    protected function compileWhereYear(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // SQLite: strftime('%Y', column)
        return "CAST(strftime('%Y', " . $this->wrap($where['column']) . ") AS INTEGER) " . $where['operator'] . " " . $value;
    }

    /**
     * Get the format for database stored dates. SQLite stores them as text.
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Compile a create table command.
     *
     * @param  Blueprint  $blueprint
     * @param  array  $columns
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, array $columns): string
    {
        $columnsSql = [];
        foreach ($columns as $column) {
            $columnsSql[] = $this->compileColumn($column);
        }

        $sql = 'CREATE TABLE ' . $this->wrapTable($blueprint->getTable()) . ' (' . implode(', ', $columnsSql) . ')';

        return $sql;
    }

    

    /**
     * Compile a single column definition.
     *
     * @param  ColumnDefinition  $column
     * @return string
     */
    protected function compileColumn(ColumnDefinition $column): string
    {
        $sql = $this->wrap($column->attributes['name']) . ' ' . $this->getType($column);

        if (isset($column->attributes['nullable']) && $column->attributes['nullable'] === false) {
            $sql .= ' NOT NULL';
        }

        if (isset($column->attributes['unique']) && $column->attributes['unique'] === true) {
            $sql .= ' UNIQUE';
        }

        if (isset($column->attributes['default'])) {
            $defaultValue = $this->normalizeDefaultValue($column->attributes['default']);
            $sql .= " DEFAULT {$defaultValue}";
        }

        if ($column->attributes['type'] === 'increments') {
            $sql .= ' PRIMARY KEY AUTOINCREMENT';
        }

        if (isset($column->attributes['after'])) {
            // SQLite does not directly support AFTER clause for ADD COLUMN.
            // This will be ignored or cause an error depending on SQLite version and context.
            // A full solution for SQLite would involve recreating the table.
            $sql .= ' /* AFTER ' . $this->wrap($column->attributes['after']) . ' */';
        }

        return $sql;
    }

    /**
     * Get the SQL type for the column.
     *
     * @param  ColumnDefinition  $column
     * @return string
     */
    protected function getType(ColumnDefinition $column): string
    {
        return match ($column->attributes['type']) {
            'increments' => 'INTEGER',
            'integer' => 'INTEGER',
            'string' => 'TEXT',
            'timestamp' => 'TEXT',
            'unsignedBigInteger' => 'INTEGER',
            'boolean' => 'INTEGER',
            'date' => 'TEXT',
            'dateTime' => 'TEXT',
            'text' => 'TEXT',
            'json' => 'TEXT',
            'double' => 'REAL',
            'float' => 'REAL',
            'uuid' => 'TEXT',
            'longText' => 'TEXT',
            'mediumText' => 'TEXT',
            'tinyInteger' => 'INTEGER',
            'smallInteger' => 'INTEGER',
            'mediumInteger' => 'INTEGER',
            'bigInteger' => 'INTEGER',
            'decimal' => 'REAL',
            'unsignedDecimal' => 'REAL',
            'char' => 'TEXT',
            'ipAddress' => 'TEXT',
            'macAddress' => 'TEXT',
            'tinyText' => 'TEXT',
            'unsignedInteger' => 'INTEGER',
            'unsignedTinyInteger' => 'INTEGER',
            'unsignedSmallInteger' => 'INTEGER',
            'unsignedMediumInteger' => 'INTEGER',
            'jsonb' => 'TEXT',
            'foreignId' => 'INTEGER',
            default => throw new \InvalidArgumentException("Invalid column type: {$column->attributes['type']}"),
        };
    }

    /**
     * Compile a drop table if exists command.
     *
     * @param  Blueprint  $blueprint
     * @param  array  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrapTable($blueprint->getTable());
    }

    public function compileDrop(Blueprint $blueprint): string
    {
        return 'DROP TABLE ' . $this->wrapTable($blueprint->getTable());
    }

    public function compileRenameTable(Blueprint $blueprint, array $command): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($blueprint->getTable()) . ' RENAME TO ' . $this->wrapTable($command['to']);
    }

    public function compileDropColumn(Blueprint $blueprint, array $command): string
    {
        // SQLite does not support DROP COLUMN directly. This would require recreating the table.
        // For simplicity, we'll return a comment indicating this limitation.
        return '-- SQLite does not support dropping columns directly. Table recreation is needed.';
    }

    public function compileRenameColumn(Blueprint $blueprint, array $command): string
    {
        // SQLite does not support RENAME COLUMN directly. This would require recreating the table.
        // For simplicity, we'll return a comment indicating this limitation.
        return '-- SQLite does not support renaming columns directly. Table recreation is needed.';
    }

    public function compilePrimary(Blueprint $blueprint, array $command): string
    {
        // SQLite handles primary keys as part of CREATE TABLE or via unique index.
        // For existing tables, adding a primary key is complex and often requires table recreation.
        // We'll create a unique index for simplicity.
        $columns = $this->columnize($command['columns']);
        $indexName = $command['index_name'] ?? $blueprint->getTable() . '_' . implode('_', $command['columns']) . '_primary';
        return "CREATE UNIQUE INDEX " . $this->wrapValueSegment($indexName) . " ON " . $this->wrapTable($blueprint->getTable()) . " (" . $columns . ")";
    }

    public function compileUnique(Blueprint $blueprint, array $command): string
    {
        $columns = $this->columnize($command['columns']);
        $indexName = $command['index_name'] ?? $blueprint->getTable() . '_' . implode('_', $command['columns']) . '_unique';
        return "CREATE UNIQUE INDEX " . $this->wrapValueSegment($indexName) . " ON " . $this->wrapTable($blueprint->getTable()) . " (" . $columns . ")";
    }

    /**
     * Compile an alter table command.
     *
     * @param  Blueprint  $blueprint
     * @param  array  $columns
     * @return string
     */
    public function compileAlter(Blueprint $blueprint, array $columns): string
    {
        $table = $this->wrapTable($blueprint->getTable());
        $statements = [];

        foreach ($columns as $column) {
            // SQLite does not support direct ALTER COLUMN for changing properties like nullability.
            // A full solution for SQLite would involve recreating the table and migrating data.
            // For now, we only support adding new columns via ALTER TABLE ADD COLUMN.
            // If a column has the 'change' attribute, it means it's an existing column being modified,
            // which is not directly supported by SQLite's ALTER TABLE syntax for column properties.
            if (!isset($column->attributes['change']) || $column->attributes['change'] === false) {
                $statements[] = 'ADD COLUMN ' . $this->compileColumn($column);
            }
        }

        if (empty($statements)) {
            return ''; // No statements to execute if only trying to change existing columns
        }

        return 'ALTER TABLE ' . $table . ' ' . implode(', ', $statements);
    }
}
