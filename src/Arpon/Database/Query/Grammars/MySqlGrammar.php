<?php

namespace Arpon\Database\Query\Grammars;

use Arpon\Database\Query\Builder as QueryBuilder;
use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\ColumnDefinition;

class MySqlGrammar extends Grammar
{
    /**
     * The grammar specific operators.
     * @var string[]
     */
    protected array $operators = ['sounds like'];


    /**
     * Wrap a value in keyword identifiers. MySQL uses backticks.
     */
    protected function wrapValueSegment(string $segment): string
    {
        return ($segment === '*') ? $segment : '`' . \str_replace('`', '``', $segment) . '`';
    }

    /**
     * Compile the "limit" portions of the query.
     * MySQL is "LIMIT offset, count" or "LIMIT count".
     * This grammar now supports "LIMIT ? OFFSET ?" which is fine for modern MySQL.
     */
    // protected function compileLimit(QueryBuilder $query, int $limit): string
    // {
    //     return 'LIMIT ' . (int) $limit;
    // }

    /**
     * Compile an update statement into SQL.
     * MySQL supports ORDER BY and LIMIT for UPDATE statements.
     */
    public function compileUpdate(QueryBuilder $query, array $values): string
    {
        $sql = parent::compileUpdate($query, $values);

        if (!empty($query->orders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->orders);
        }

        if (!\is_null($query->limit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->limit); // No offset in MySQL UPDATE LIMIT
        }

        return $sql;
    }

    /**
     * Compile a delete statement into SQL.
     * MySQL supports ORDER BY and LIMIT for DELETE statements.
     */
    public function compileDelete(QueryBuilder $query): string
    {
        $table = $this->wrapTable($query->from);
        // Use already compiled wheres from parent grammar if they exist
        $wheres = !empty($query->wheres) ? \substr($this->compileWheres($query, $query->wheres), 6) : ''; // Remove 'WHERE '
        $sql = \trim("DELETE FROM {$table}" . ($wheres ? ' WHERE ' . $wheres : ''));


        if (!empty($query->orders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->orders);
        }

        if (!\is_null($query->limit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Compile a "truncate table" statement.
     */
    public function compileTruncate(QueryBuilder $query): array
    {
        return ['TRUNCATE TABLE ' . $this->wrapTable($query->from) => []];
    }

    // --- MySQL Specific Date/Time Where Compilers ---
    protected function compileWhereDate(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return 'DATE(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }
    protected function compileWhereTime(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return 'TIME(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }
    protected function compileWhereDay(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return 'DAYOFMONTH(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }
    protected function compileWhereMonth(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return 'MONTH(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }
    protected function compileWhereYear(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return 'YEAR(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    public function compileAlter(\Arpon\Database\Schema\Blueprint $blueprint, array $columns): string
    {
        $table = $this->wrapTable($blueprint->getTable());
        $statements = [];

        foreach ($columns as $column) {
            if (isset($column->attributes['change']) && $column->attributes['change'] === true) {
                $statements[] = 'MODIFY ' . $this->compileColumn($column);
            } else {
                $statements[] = 'ADD ' . $this->compileColumn($column);
            }
        }

        return 'ALTER TABLE ' . $table . ' ' . implode(', ', $statements);
    }

    /**
     * Compile a create table command.
     *
     * @param  \Arpon\Database\Schema\Blueprint  $blueprint
     * @param  array  $columns
     * @return string
     */
    public function compileCreate(\Arpon\Database\Schema\Blueprint $blueprint, array $columns): string
    {
        $columnsSql = [];
        foreach ($columns as $column) {
            $columnsSql[] = $this->compileColumn($column);
        }
        return 'CREATE TABLE ' . $this->wrapTable($blueprint->getTable()) . ' (' . implode(', ', $columnsSql) . ')';
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
            $sql .= ' AUTO_INCREMENT PRIMARY KEY';
        }

        if (isset($column->attributes['after'])) {
            $sql .= ' AFTER ' . $this->wrap($column->attributes['after']);
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
            'increments' => 'BIGINT UNSIGNED',
            'integer' => 'INT',
            'string' => 'VARCHAR(' . ($column->attributes['parameters']['length'] ?? 255) . ')',
            'timestamp' => 'TIMESTAMP',
            'unsignedBigInteger' => 'BIGINT UNSIGNED',
            'boolean' => 'TINYINT(1)',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'text' => 'TEXT',
            'json' => 'JSON',
            'double' => "DOUBLE(" . ($column->attributes['parameters']['total'] ?? 8) . ", " . ($column->attributes['parameters']['places'] ?? 2) . ')',
            'float' => "FLOAT(" . ($column->attributes['parameters']['total'] ?? 8) . ", " . ($column->attributes['parameters']['places'] ?? 2) . ')',
            'uuid' => 'CHAR(36)',
            'longText' => 'LONGTEXT',
            'mediumText' => 'MEDIUMTEXT',
            'tinyInteger' => 'TINYINT',
            'smallInteger' => 'SMALLINT',
            'mediumInteger' => 'MEDIUMINT',
            'bigInteger' => 'BIGINT',
            'decimal' => "DECIMAL(" . ($column->attributes['parameters']['total'] ?? 8) . ", " . ($column->attributes['parameters']['places'] ?? 2) . ')',
            'unsignedDecimal' => "DECIMAL(" . ($column->attributes['parameters']['total'] ?? 8) . ", " . ($column->attributes['parameters']['places'] ?? 2) . ') UNSIGNED',
            'char' => 'CHAR(' . ($column->attributes['parameters']['length'] ?? 255) . ')',
            'ipAddress' => 'VARCHAR(45)',
            'macAddress' => 'VARCHAR(17)',
            'tinyText' => 'TINYTEXT',
            'unsignedInteger' => 'INT UNSIGNED',
            'unsignedTinyInteger' => 'TINYINT UNSIGNED',
            'unsignedSmallInteger' => 'SMALLINT UNSIGNED',
            'unsignedMediumInteger' => 'MEDIUMINT UNSIGNED',
            'jsonb' => 'JSON',
            'foreignId' => 'BIGINT UNSIGNED',
            default => throw new \InvalidArgumentException("Invalid column type: {" . $column->attributes['type'] . "}"),
        };
    }

    public function compileDropColumn(Blueprint $blueprint, array $command): string
    {
        $columns = implode(', ', array_map(fn($column) => 'DROP ' . $this->wrap($column), $command['columns']));
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " " . $columns;
    }

    public function compileRenameColumn(Blueprint $blueprint, array $command): string
    {
        // MySQL requires re-specifying the column definition for RENAME COLUMN
        // This is a simplified version and assumes the new column definition is not changing type etc.
        // A more robust solution would require fetching the old column definition.
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " CHANGE " . $this->wrap($command['from']) . " " . $this->wrap($command['to']) . " " . $this->getTypeString([]); // Placeholder type
    }

    public function compilePrimary(Blueprint $blueprint, array $command): string
    {
        $columns = $this->columnize($command['columns']);
        $indexName = $command['index_name'] ?? $blueprint->getTable() . '_' . implode('_', $command['columns']) . '_primary';
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " ADD CONSTRAINT " . $this->wrapValueSegment($indexName) . " PRIMARY KEY (" . $columns . ")";
    }

    public function compileUnique(Blueprint $blueprint, array $command): string
    {
        $columns = $this->columnize($command['columns']);
        $indexName = $command['index_name'] ?? $blueprint->getTable() . '_' . implode('_', $command['columns']) . '_unique';
        return "CREATE UNIQUE INDEX " . $this->wrapValueSegment($indexName) . " ON " . $this->wrapTable($blueprint->getTable()) . " (" . $columns . ")";
    }

    public function compileForeign(Blueprint $blueprint, array $command): string
    {
        $sql = sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $this->wrapTable($blueprint->getTable()),
            $this->wrapValueSegment($command['index_name'] ?? $blueprint->getTable() . '_' . $command['column'] . '_foreign'),
            $this->wrap($command['column']),
            $this->wrapTable($command['on']),
            $this->wrap($command['references'])
        );

        if (isset($command['onDelete'])) {
            $sql .= ' ON DELETE ' . strtoupper($command['onDelete']);
        }

        if (isset($command['onUpdate'])) {
            $sql .= ' ON UPDATE ' . strtoupper($command['onUpdate']);
        }

        return $sql;
    }

    /**
     * Compile a drop table if exists command.
     *
     * @param  \Arpon\Database\Schema\Blueprint  $blueprint
     * @param  array  $command
     * @return string
     */
    public function compileDropIfExists(\Arpon\Database\Schema\Blueprint $blueprint): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrapTable($blueprint->getTable());
    }

    public function compileDrop(\Arpon\Database\Schema\Blueprint $blueprint): string
    {
        return 'DROP TABLE ' . $this->wrapTable($blueprint->getTable());
    }

    public function compileRenameTable(\Arpon\Database\Schema\Blueprint $blueprint, array $command): string
    {
        return 'RENAME TABLE ' . $this->wrapTable($blueprint->getTable()) . ' TO ' . $this->wrapTable($command['to']);
    }
}
