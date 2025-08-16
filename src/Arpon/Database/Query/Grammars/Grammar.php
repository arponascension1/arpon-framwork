<?php

namespace Arpon\Database\Query\Grammars;

use Arpon\Database\Query\Builder as QueryBuilder;
use Arpon\Database\Query\Expression;
use Arpon\Database\Query\JoinClause;
use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\ColumnDefinition;

// If JoinClause is used for type hinting

abstract class Grammar
{
    /**
     * The components that make up a select query.
     * Order matters for SQL generation.
     * @var string[]
     */
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Get the grammar specific operators.
     * This can be overridden by specific grammars if they support additional operators.
     * @return string[]
     */
    public function getOperators(): array
    {
        return [];
    }


    /**
     * Compile a select query into SQL.
     */
    public function compileSelect(QueryBuilder $query): string
    {
        if (empty($query->columns) && empty($query->aggregate)) {
            $query->columns = ['*'];
        }

        $sql = [];
        foreach ($this->selectComponents as $component) {
            // Check if the property exists and is not null.
            // For array properties like 'wheres', check if they are non-empty as well,
            // though null check is generally sufficient if they are initialized as arrays.
            if (isset($query->$component) && !is_null($query->$component)) {
                // For array components like 'wheres', 'joins', ensure they are not empty
                if (\is_array($query->$component) && empty($query->$component) && $component !== 'columns' && $component !== 'aggregate') {
                    // Skip empty array components unless it's columns/aggregate where '*' is default
                    // or if an empty array has a specific meaning (e.g., no wheres means no WHERE clause).
                    // The individual compile methods should handle empty arrays appropriately.
                }

                $method = 'compile' . \ucfirst($component);
                if (\method_exists($this, $method)) {
                    $compiled = $this->$method($query, $query->$component);
                    if (!empty($compiled) || (\is_string($compiled) && $compiled !== '')) {
                        $sql[$component] = $compiled;
                    }
                }
            }
        }
        return \trim(\implode(' ', \array_filter($sql)));
    }

    protected function compileAggregate(QueryBuilder $query, array $aggregate): string
    {
        $column = $this->columnize($aggregate['columns']);
        if (\strtoupper($aggregate['function']) === 'COUNT' && ($column === '*' || $column === '')) {
            $column = '*';
        }
        return 'SELECT ' . $aggregate['function'] . '(' . $column . ') AS aggregate';
    }

    protected function compileColumns(QueryBuilder $query, array $columns): string
    {
        if (!\is_null($query->aggregate)) {
            return '';
        }
        $compiledColumns = [];
        foreach ($columns as $column) {
            $compiledColumns[] = (string) $column; // Directly cast to string for raw expressions
        }
        return 'SELECT ' . \implode(', ', $compiledColumns);
    }

    protected function compileFrom(QueryBuilder $query, string $table): string
    {
        return 'FROM ' . $this->wrapTable($table);
    }

    protected function compileJoins(QueryBuilder $query, array $joins): string
    {
        return \implode(' ', \array_map(function (JoinClause $join) use ($query) { // Type hint JoinClause
            $table = $this->wrapTable($join->table);
            // The JoinClause itself should now manage its ON conditions as "wheres" internally for compilation
            // or provide a dedicated method for compiling its ON clauses.
            // Let's assume JoinClause's getOnClauses() returns an array of where-like structures.
            $on = $this->compileWheresForJoins($query, $join->getOnClauses()); // Use a dedicated method for join ONs
            return \trim("{$join->type} JOIN {$table} {$on}");
        }, $joins));
    }

    // Helper to compile ON clauses for JOINs, similar to compileWheres but without "WHERE" keyword
    protected function compileWheresForJoins(QueryBuilder $query, array $wheres): string
    {
        if (empty($wheres)) {
            return '';
        }
        $sql = [];
        foreach ($wheres as $i => $where) {
            $prefix = ($i === 0) ? '' : ($where['boolean'] . ' ');
            $methodName = "compileWhere{$where['type']}";
            if (\method_exists($this, $methodName)) {
                $sql[] = $prefix . $this->$methodName($query, $where);
            } else {
                // Fallback or error for unknown where type in join
                throw new \RuntimeException("Unknown where type [{$where['type']}] encountered in join clause.");
            }
        }
        return 'ON ' . \implode(' ', $sql);
    }


    protected function compileWheres(QueryBuilder $query, array $wheres): string
    {
        if (empty($wheres)) {
            return '';
        }

        $sql = [];
        foreach ($wheres as $i => $where) {
            $prefix = ($i === 0) ? '' : ($where['boolean'] . ' ');
            // Dynamically call the appropriate compileWhere<Type> method
            $methodName = "compileWhere{$where['type']}";
            if (\method_exists($this, $methodName)) {
                $sql[] = $prefix . $this->$methodName($query, $where);
            } else {
                throw new \RuntimeException("Unknown where type [{$where['type']}] encountered.");
            }
        }
        return 'WHERE ' . \implode(' ', $sql);
    }

    protected function compileWhereBasic(QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        return $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . $value;
    }

    protected function compileWhereNested(QueryBuilder $query, array $where): string
    {
        return '(' . \substr($this->compileWheres($where['query'], $where['query']->wheres), 6) . ')';
    }

    protected function compileWhereIn(QueryBuilder $query, array $where): string
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }
        $placeholders = $this->parameterize($where['values']);
        return $this->wrap($where['column']) . ' IN (' . $placeholders . ')';
    }

    protected function compileWhereNotIn(QueryBuilder $query, array $where): string
    {
        if (empty($where['values'])) {
            return '1 = 1';
        }
        $placeholders = $this->parameterize($where['values']);
        return $this->wrap($where['column']) . ' NOT IN (' . $placeholders . ')';
    }

    protected function compileWhereInSub(QueryBuilder $query, array $where): string
    {
        $select = $this->compileSelect($where['query']); // Compile the sub-select query
        return $this->wrap($where['column']).' IN ('.$select.')';
    }
    protected function compileWhereNotInSub(QueryBuilder $query, array $where): string
    {
        $select = $this->compileSelect($where['query']);
        return $this->wrap($where['column']).' NOT IN ('.$select.')';
    }


    protected function compileWhereNull(QueryBuilder $query, array $where): string
    {
        return $this->wrap($where['column']) . ' IS NULL';
    }

    protected function compileWhereNotNull(QueryBuilder $query, array $where): string
    {
        return $this->wrap($where['column']) . ' IS NOT NULL';
    }

    protected function compileWhereRaw(QueryBuilder $query, array $where): string
    {
        return $where['sql'];
    }

    // --- NEW Where Clause Compilers ---
    protected function compileWhereDate(QueryBuilder $query, array $where): string
    {
        return $this->dateBasedWhere('DATE', $query, $where);
    }
    protected function compileWhereTime(QueryBuilder $query, array $where): string
    {
        return $this->dateBasedWhere('TIME', $query, $where);
    }
    protected function compileWhereDay(QueryBuilder $query, array $where): string
    {
        return $this->dateBasedWhere('DAY', $query, $where);
    }
    protected function compileWhereMonth(QueryBuilder $query, array $where): string
    {
        return $this->dateBasedWhere('MONTH', $query, $where);
    }
    protected function compileWhereYear(QueryBuilder $query, array $where): string
    {
        return $this->dateBasedWhere('YEAR', $query, $where);
    }

    /**
     * Helper for compiling date-based where clauses.
     * This will likely be overridden by specific grammars (MySQL, SQLite).
     */
    protected function dateBasedWhere(string $type, QueryBuilder $query, array $where): string
    {
        $value = $this->parameter($where['value']);
        // Generic approach, specific databases will have better functions
        return \strtoupper($type) . '(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    protected function compileWhereBetween(QueryBuilder $query, array $where): string
    {
        $between = $where['not'] ? 'NOT BETWEEN' : 'BETWEEN';
        // Values are already bound as two separate parameters.
        return $this->wrap($where['column']) . ' ' . $between . ' ? AND ?';
    }

    protected function compileWhereColumn(QueryBuilder $query, array $where): string
    {
        // Compares two columns. Neither 'first' nor 'second' (which holds the second column name) are parameters.
        return $this->wrap($where['first']) . ' ' . $where['operator'] . ' ' . $this->wrap($where['second']);
    }

    // --- End NEW Where Clause Compilers ---


    protected function compileGroups(QueryBuilder $query, array $groups): string
    {
        return 'GROUP BY ' . $this->columnize($groups);
    }

    protected function compileHavings(QueryBuilder $query, array $havings): string
    {
        if (empty($havings)) return '';
        $sql = [];
        foreach ($havings as $i => $having) {
            $prefix = ($i === 0) ? '' : ($having['boolean'] . ' ');
            $sql[] = $prefix . $this->wrap($having['column']) . ' ' . $having['operator'] . ' ' . $this->parameter($having['value']);
        }
        return 'HAVING ' . \implode(' ', $sql);
    }

    protected function compileOrders(QueryBuilder $query, array $orders): string
    {
        if (empty($orders)) {
            return '';
        }
        $sql = [];
        foreach ($orders as $order) {
            $sql[] = $this->wrap($order['column']) . ' ' . $order['direction'];
        }
        return 'ORDER BY ' . \implode(', ', $sql);
    }

    protected function compileLimit(QueryBuilder $query, int $limit): string
    {
        return 'LIMIT ' . (int) $limit;
    }

    protected function compileOffset(QueryBuilder $query, int $offset): string
    {
        return 'OFFSET ' . (int) $offset;
    }


    /**
     * Compile an insert statement into SQL.
     */
    public function compileInsert(QueryBuilder $query, array $values): string
    {
        $table = $this->wrapTable($query->from);
        if (empty($values)) {
            return "INSERT INTO {$table} DEFAULT VALUES";
        }

        if (!\is_array(\reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(\array_keys(\reset($values)));
        $placeholders = [];
        foreach ($values as $record) {
            $placeholders[] = '(' . $this->parameterize(\array_values($record)) . ')';
        }

        return "INSERT INTO {$table} ({$columns}) VALUES " . \implode(', ', $placeholders);
    }

    public function compileInsertGetId(QueryBuilder $query, array $values, string $sequence): string
    {
        return $this->compileInsert($query, $values); // Grammar specific may override if sequence needed in SQL
    }

    public function compileUpdate(QueryBuilder $query, array $values): string
    {
        $table = $this->wrapTable($query->from);
        $columns = [];
        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key) . ' = ' . $this->parameter($value);
        }
        $columnsSql = \implode(', ', $columns);
        // Get Wheres for update, which may be empty if it's a full table update (dangerous)
        $wheres = !empty($query->wheres) ? $this->compileWheres($query, $query->wheres) : '';


        return \trim("UPDATE {$table} SET {$columnsSql} {$wheres}");
    }

    public function compileDelete(QueryBuilder $query): string
    {
        $table = $this->wrapTable($query->from);
        $wheres = !empty($query->wheres) ? $this->compileWheres($query, $query->wheres) : '';
        return \trim("DELETE FROM {$table} {$wheres}");
    }

    public function wrapTable(string $table): string
    {
        if ($this->isExpression($table)) {
            return $table; // Don't wrap expressions
        }
        // Handle "table as alias"
        if (\stripos($table, ' as ') !== false) {
            $segments = \preg_split('/\s+as\s+/i', $table);
            return $this->wrapTable($segments[0]).' AS '.$this->wrapValueSegment($segments[1]);
        }
        return $this->wrap($table);
    }

    public function wrap(string $value): string
    {
        if ($this->isExpression($value)) {
            return (string) $value; // Return the expression directly
        }
        if (\stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value);
        }
        if (\str_contains($value, '.')) {
            return \implode('.', \array_map([$this, 'wrapValueSegment'], \explode('.', $value)));
        }
        return $this->wrapValueSegment($value);
    }

    protected function wrapValueSegment(string $segment): string
    {
        if ($this->isExpression($segment)) {
            return (string) $segment;
        }
        return ($segment === '*') ? $segment : '"' . \str_replace('"', '""', $segment) . '"';
    }

    protected function wrapAliasedValue(string $valueWithAlias): string
    {
        $segments = \preg_split('/\s+as\s+/i', $valueWithAlias);
        return $this->wrap($segments[0]) . ' AS ' . $this->wrapValueSegment($segments[1]);
    }

    public function columnize(array $columns): string
    {
        return \implode(', ', \array_map(function ($column) {
            return $this->isExpression($column) ? (string) $column : $this->wrap($column);
        }, $columns));
    }

    public function parameterize(array $values): string
    {
        return \implode(', ', \array_map([$this, 'parameter'], $values));
    }

    public function parameter(mixed $value): string
    {
        return $this->isExpression($value) ? (string) $value : '?';
    }

    protected function isExpression(mixed $value): bool
    {
        return $value instanceof Expression;
    }

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    protected function getTypeBoolean(): string
    {
        return 'BOOLEAN';
    }

    protected function getTypeDate(): string
    {
        return 'DATE';
    }

    protected function getTypeDateTime(): string
    {
        return 'DATETIME';
    }

    protected function getTypeJson(): string
    {
        return 'JSON';
    }

    protected function getTypeDouble(array $parameters): string
    {
        $total = $parameters['total'] ?? 8;
        $places = $parameters['places'] ?? 2;
        return "DOUBLE({$total}, {$places})";
    }

    protected function getTypeFloat(array $parameters): string
    {
        $total = $parameters['total'] ?? 8;
        $places = $parameters['places'] ?? 2;
        return "FLOAT({$total}, {$places})";
    }

    protected function getTypeUuid(): string
    {
        return 'CHAR(36)';
    }

    protected function getTypeLongText(): string
    {
        return 'LONGTEXT';
    }

    protected function getTypeMediumText(): string
    {
        return 'MEDIUMTEXT';
    }

    protected function getTypeTinyInteger(): string
    {
        return 'TINYINT';
    }

    protected function getTypeSmallInteger(): string
    {
        return 'SMALLINT';
    }

    protected function getTypeMediumInteger(): string
    {
        return 'MEDIUMINT';
    }

    protected function getTypeBigInteger(): string
    {
        return 'BIGINT';
    }

    protected function getTypeDecimal(array $parameters): string
    {
        $total = $parameters['total'] ?? 8;
        $places = $parameters['places'] ?? 2;
        return "DECIMAL({$total}, {$places})";
    }

    protected function getTypeUnsignedDecimal(array $parameters): string
    {
        $total = $parameters['total'] ?? 8;
        $places = $parameters['places'] ?? 2;
        return "DECIMAL({$total}, {$places}) UNSIGNED";
    }

    protected function getTypeChar(array $parameters): string
    {
        $length = $parameters['length'] ?? 255;
        return "CHAR({$length})";
    }

    protected function getTypeIpAddress(): string
    {
        return 'VARCHAR(45)'; // IPv6 can be up to 45 chars
    }

    protected function getTypeMacAddress(): string
    {
        return 'VARCHAR(17)'; // MAC address format XX:XX:XX:XX:XX:XX
    }

    protected function getTypeTinyText(): string
    {
        return 'TINYTEXT';
    }

    protected function getTypeUnsignedInteger(): string
    {
        return 'INTEGER UNSIGNED';
    }

    protected function getTypeUnsignedTinyInteger(): string
    {
        return 'TINYINT UNSIGNED';
    }

    protected function getTypeUnsignedSmallInteger(): string
    {
        return 'SMALLINT UNSIGNED';
    }

    protected function getTypeUnsignedMediumInteger(): string
    {
        return 'MEDIUMINT UNSIGNED';
    }

    protected function getTypeJsonb(): string
    {
        return 'JSONB';
    }

    protected function getTypeForeignId(): string
    {
        return 'BIGINT UNSIGNED';
    }

    protected function getTypeInteger(): string
    {
        return 'INTEGER';
    }

    protected function getTypeString(array $parameters): string
    {
        $length = $parameters['length'] ?? 255;
        return "VARCHAR({$length})";
    }

    protected function getTypeTimestamp(): string
    {
        return 'TIMESTAMP';
    }

    protected function getTypeUnsignedBigInteger(): string
    {
        return 'BIGINT UNSIGNED';
    }

    protected function getTypeIncrements(): string
    {
        return 'INTEGER PRIMARY KEY AUTOINCREMENT'; // SQLite specific, will need to be overridden
    }

    protected function getTypeText(): string
    {
        return 'TEXT';
    }

    protected function compileColumn(ColumnDefinition $column): string
    {
        $sql = $this->{"getType" . ucfirst($column->attributes['type'])}($column->attributes['parameters'] ?? []);

        if (isset($column->attributes['nullable']) && $column->attributes['nullable'] === false) {
            $sql .= ' NOT NULL';
        }

        if (isset($column->attributes['default'])) {
            $defaultValue = $this->normalizeDefaultValue($column->attributes['default']);
            $sql .= " DEFAULT {$defaultValue}";
        }

        if (isset($column->attributes['unique']) && $column->attributes['unique'] === true) {
            $sql .= ' UNIQUE';
        }

        if (isset($column->attributes['autoIncrement']) && $column->attributes['autoIncrement'] === true) {
            $sql .= ' AUTOINCREMENT'; // SQLite specific
        }

        if (isset($column->attributes['primary']) && $column->attributes['primary'] === true) {
            $sql .= ' PRIMARY KEY';
        }

        if (isset($column->attributes['first']) && $column->attributes['first'] === true) {
            $sql .= ' FIRST';
        }

        if (isset($column->attributes['after'])) {
            $sql .= ' AFTER ' . $this->wrap($column->attributes['after']);
        }

        return $sql;
    }

    protected function normalizeDefaultValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_numeric($value)) {
            return (string) $value;
        } else {
            return "'" . str_replace("'", "''", $value) . "'";
        }
    }

    public function compileCreate(Blueprint $blueprint, array $columns): string
    {
        $columnsSql = [];
        foreach ($columns as $column) {
            $columnsSql[] = $this->compileColumn($column);
        }

        $sql = "CREATE TABLE " . $this->wrapTable($blueprint->getTable()) . " (" . implode(', ', $columnsSql) . ')';

        return $sql;
    }

    public function compileAlter(Blueprint $blueprint, array $columns): string
    {
        $sql = [];
        foreach ($columns as $column) {
            $sql[] = "ADD COLUMN " . $this->compileColumn($column);
        }
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " " . implode(', ', $sql);
    }

    public function compileDropIfExists(Blueprint $blueprint): string
    {
        return "DROP TABLE IF EXISTS " . $this->wrapTable($blueprint->getTable());
    }

    public function compileDrop(Blueprint $blueprint): string
    {
        return "DROP TABLE " . $this->wrapTable($blueprint->getTable());
    }

    public function compileRenameTable(Blueprint $blueprint, array $command): string
    {
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " RENAME TO " . $this->wrapTable($command['to']);
    }

    public function compileDropColumn(Blueprint $blueprint, array $command): string
    {
        $columns = $this->columnize($command['columns']);
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " DROP COLUMN " . $columns;
    }

    public function compileRenameColumn(Blueprint $blueprint, array $command): string
    {
        return "ALTER TABLE " . $this->wrapTable($blueprint->getTable()) . " RENAME COLUMN " . $this->wrap($command['from']) . " TO " . $this->wrap($command['to']);
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
        $sql = "FOREIGN KEY (" . $this->wrap($command['column']) . ") REFERENCES " . $this->wrapTable($command['on']) . " (" . $this->wrap($command['references']) . ")";

        if (isset($command['onDelete'])) {
            $sql .= " ON DELETE " . $command['onDelete'];
        }

        if (isset($command['onUpdate'])) {
            $sql .= " ON UPDATE " . $command['onUpdate'];
        }

        return $sql;
    }

    public function compileIndex(Blueprint $blueprint, array $command): string
    {
        $columns = $this->columnize($command['columns']);
        $indexName = $command['index_name'] ?? $blueprint->getTable() . '_' . implode('_', $command['columns']) . '_index';
        return "CREATE INDEX " . $this->wrapValueSegment($indexName) . " ON " . $this->wrapTable($blueprint->getTable()) . " (" . $columns . ")";
    }

    /**
     * Check if the given string is a JSON selector.
     *
     * @param string $value
     * @return bool
     */
    protected function isJsonSelector(string $value): bool
    {
        return \str_contains($value, '->');
    }

}
