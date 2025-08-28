<?php

namespace Arpon\Database\Query;

use Arpon\Database\Pagination\LengthAwarePaginator;
use Closure;
use Arpon\Database\DatabaseManager;
use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;
use Arpon\Database\ORM\Relations\Relation;
use Arpon\Database\Query\Grammars\Grammar;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Throwable;

// For nested where, joins, etc.
// For model context
// For returning collections of models
// For transactions
// For has/whereHas
// Add this line

class Builder
{
    protected PDO $pdo;
    public Grammar $grammar;
    protected DatabaseManager $dbManager; // For transaction management

    // Query components
    public ?array $aggregate = null;
    public ?array $columns = null;
    public ?string $from = null;
    public array $joins = [];
    public array $wheres = [];
    public ?array $groups = null;
    public ?array $havings = null;
    public ?array $orders = null;
    public ?int $limit = null;
    public ?int $offset = null;
    // public array $unions = null; // For future UNION support

    /**
     * All of the available clause operators.
     * @var string[]
     */
    public array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * The current model instance for hydration (if applicable).
     */
    protected ?Model $model = null;

    /**
     * Relations to eager load.
     * @var array
     */
    protected array $eagerLoad = [];

    /**
     * The bindings for the query.
     * Grouped by type for clarity and correct ordering.
     * @var array
     */
    public array $bindings = [
        'select' => [],
        'from'   => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
    ];


    public function __construct(PDO $pdo, Grammar $grammar, DatabaseManager $dbManager)
    {
        $this->pdo = $pdo;
        $this->grammar = $grammar;
        $this->dbManager = $dbManager;
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getModelInstance(): ?Model
    {
        return $this->model;
    }

    public function table(string $table, ?string $as = null): static
    {
        $this->from = $as ? "{$table} AS {$this->grammar->wrapTable($as)}" : $table;
        return $this;
    }

    public function select(array|string $columns = ['*']): static
    {
        $this->columns = \is_array($columns) ? $columns : \func_get_args();
        $this->bindings['select'] = [];
        return $this;
    }

    public function addSelect(array|string $column): static
    {
        $column = \is_array($column) ? $column : \func_get_args();
        $this->columns = \array_merge((array) $this->columns, $column);
        return $this;
    }

    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->addSelect(new Expression($expression)); // Wrap raw expression
        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }
        return $this;
    }


    // --- Join Methods ---
    public function join(string $table, string|Closure $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): static
    {
        $join = new JoinClause($this, $type, $table);

        if ($first instanceof Closure) {
            $first($join);
        } else {
            $join->on($first, $operator, $second);
        }
        $this->joins[] = $join;
        // Bindings for join conditions should be handled by JoinClause::getBindings and added here
        $this->addBinding($join->getBindings(), 'join');
        return $this;
    }

    public function leftJoin(string $table, string|Closure $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string|Closure $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // --- Where Methods ---
    public function where(string|Closure|array $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if (\is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        if ($column instanceof Closure && \is_null($operator)) {
            return $this->whereNested($column, $boolean);
        }

        if (\func_num_args() === 2 || (\func_num_args() === 3 && $this->isInvalidOperator($operator))) {
            [$value, $operator] = [$operator, '='];
        } elseif ($this->isInvalidOperator($operator)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        $type = 'Basic';
        $this->wheres[] = \compact('type', 'column', 'operator', 'value', 'boolean');

        if (!($value instanceof Expression)) {
            $this->addBinding($value, 'where');
        }
        return $this;
    }

    protected function addArrayOfWheres(array $column, string $boolean, string $method = 'where'): static
    {
        return $this->whereNested(function (self $query) use ($column, $method) {
            foreach ($column as $key => $value) {
                if (\is_numeric($key) && \is_array($value)) {
                    $query->{$method}(...$value);
                } else {
                    $query->{$method}($key, '=', $value);
                }
            }
        }, $boolean);
    }


    protected function isInvalidOperator(?string $operator): bool
    {
        return !\is_null($operator) && !\in_array(\strtolower((string)$operator), $this->operators, true) &&
            !\in_array(\strtolower((string)$operator), $this->grammar->getOperators(), true);
    }


    public function orWhere(string|Closure|array $column, mixed $operator = null, mixed $value = null): static
    {
        if (\func_num_args() === 2 || (\func_num_args() === 3 && $this->isInvalidOperator($operator))) {
            [$value, $operator] = [$operator, '='];
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    protected function whereNested(Closure $callback, string $boolean = 'AND'): static
    {
        $query = $this->newQuery();
        $callback($query);

        if (!empty($query->wheres)) {
            $type = 'Nested';
            $this->wheres[] = \compact('type', 'query', 'boolean');
        }
        return $this;
    }

    public function whereIn(string $column, mixed $values, string $boolean = 'AND', bool $not = false): static
    {
        $type = $not ? 'NotIn' : 'In';
        if ($values instanceof Closure) {
            return $this->whereInSub($column, $values, $boolean, $not);
        }
        if ($values instanceof self) {
            return $this->whereInSub($column, function(self $query) use ($values) {
                // This needs careful handling of how subquery SQL and bindings are generated/merged
                // For now, let's assume the subquery $values already has its SELECTs
                // The grammar's compileWhereInSub will handle compiling $values->toSql()
                $query->selectRaw('('.$values->toSql().')'); // Placeholder, this isn't quite right for grammar
                $query->addBinding($values->getBindings()['where'] ?? [], 'where'); // Add subquery's where bindings
            }, $boolean, $not);
        }
        if (empty($values)) {
            return $this->whereRaw($not ? '1 = 1' : '0 = 1', [], $boolean);
        }

        $this->wheres[] = \compact('type', 'column', 'values', 'boolean');
        $this->addBinding($values, 'where'); // This will add array of values for IN
        return $this;
    }
    protected function whereInSub(string $column, Closure $callback, string $boolean, bool $not): static
    {
        $type = $not ? 'NotInSub' : 'InSub';
        $query = $this->newQuery();
        $callback($query);
        $this->wheres[] = ['type' => $type, 'column' => $column, 'query' => $query, 'boolean' => $boolean];
        return $this;
    }

    public function orWhereIn(string $column, mixed $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }
    public function whereNotIn(string $column, mixed $values, string $boolean = 'AND'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }
    public function orWhereNotIn(string $column, mixed $values): static
    {
        return $this->whereNotIn($column, $values, 'OR');
    }


    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $type = $not ? 'NotNull' : 'Null';
        $this->wheres[] = \compact('type', 'column', 'boolean');
        return $this;
    }
    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'OR');
    }
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->whereNull($column, $boolean, true);
    }
    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'OR');
    }

    protected function addDateBasedWhere(string $type, string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        if (\func_num_args() === 4 && !\is_string($value)) {
            [$value, $operator] = [$operator, '='];
        } elseif ( (\func_num_args() === 3 || (\func_num_args() === 4 && \is_null($value))) && $this->isInvalidOperator($operator) ) {
            // Handles whereDate('column', 'value')
            $value = $operator;
            $operator = '=';
        } elseif ($this->isInvalidOperator($operator)) {
            throw new InvalidArgumentException('Illegal operator and value combination for date where.');
        }


        $this->wheres[] = \compact('type', 'column', 'operator', 'value', 'boolean');
        if (!($value instanceof Expression)) {
            $this->addBinding($value, 'where');
        }
        return $this;
    }

    public function whereDate(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        if(\func_num_args() === 3 && \is_null($value) && !$this->isInvalidOperator($operator)) { /* operator is operator, value is null */ }
        else if (\func_num_args() === 3) { $boolean = 'AND';} // operator is value, value is null (becomes boolean)
        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }
    public function orWhereDate(string $column, string $operator, mixed $value = null): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->addDateBasedWhere('Date', $column, $operator, $value, 'OR');
    }

    public function whereTime(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        if(\func_num_args() === 3 && \is_null($value) && !$this->isInvalidOperator($operator)) { /* keep as is */ }
        else if (\func_num_args() === 3) { $boolean = 'AND';}
        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }
    public function orWhereTime(string $column, string $operator, mixed $value = null): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->addDateBasedWhere('Time', $column, $operator, $value, 'OR');
    }

    public function whereDay(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        if(\func_num_args() === 3 && \is_null($value) && !$this->isInvalidOperator($operator)) { /* keep as is */ }
        else if (\func_num_args() === 3) { $boolean = 'AND';}
        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }
    public function orWhereDay(string $column, string $operator, mixed $value = null): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->addDateBasedWhere('Day', $column, $operator, $value, 'OR');
    }

    public function whereMonth(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        if(\func_num_args() === 3 && \is_null($value) && !$this->isInvalidOperator($operator)) { /* keep as is */ }
        else if (\func_num_args() === 3) { $boolean = 'AND';}
        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }
    public function orWhereMonth(string $column, string $operator, mixed $value = null): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->addDateBasedWhere('Month', $column, $operator, $value, 'OR');
    }

    public function whereYear(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        if(\func_num_args() === 3 && \is_null($value) && !$this->isInvalidOperator($operator)) { /* keep as is */ }
        else if (\func_num_args() === 3) { $boolean = 'AND';}
        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }
    public function orWhereYear(string $column, string $operator, mixed $value = null): static
    {
        if(\func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->addDateBasedWhere('Year', $column, $operator, $value, 'OR');
    }

    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $type = 'Between';
        if (\count($values) !== 2) {
            throw new InvalidArgumentException('whereBetween values must be an array of two elements.');
        }
        $this->wheres[] = \compact('type', 'column', 'values', 'boolean', 'not');
        $this->addBinding($values[0], 'where');
        $this->addBinding($values[1], 'where');
        return $this;
    }
    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'OR');
    }
    public function whereNotBetween(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }
    public function orWhereNotBetween(string $column, array $values): static
    {
        return $this->whereNotBetween($column, $values, 'OR');
    }

    public function whereColumn(string|array $first, ?string $operator = null, ?string $second = null, string $boolean = 'AND'): static
    {
        if (\is_array($first)) {
            foreach ($first as $item) {
                // Assuming item is an array [first, operator, second] or [first, second]
                $this->whereColumn(...array_merge($item, [$boolean]));
            }
            return $this;
        }

        if ($this->isInvalidOperator($operator)) {
            [$second, $operator] = [$operator, '='];
        }
        if (\is_null($second)) {
            $second = $operator; // This implies operator was actually the second column
            $operator = '=';
        }

        $type = 'Column';
        $this->wheres[] = ['type' => $type, 'first' => $first, 'operator' => $operator, 'second' => $second, 'boolean' => $boolean];
        return $this;
    }
    public function orWhereColumn(string|array $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->whereColumn($first, $operator, $second, 'OR');
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->wheres[] = ['type' => 'Raw', 'sql' => $sql, 'boolean' => $boolean];
        $this->addBinding($bindings, 'where');
        return $this;
    }
    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }


    // --- GroupBy / Having ---
    public function groupBy(array|string ...$groups): static
    {
        foreach ($groups as $group) {
            $this->groups = \array_merge((array) $this->groups, \is_array($group) ? $group : [$group]);
        }
        return $this;
    }

    public function having(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        $type = 'Basic';
        if (\func_num_args() === 2 || (\func_num_args() === 3 && $this->isInvalidOperator($operator))) {
            $value = $operator;
            $operator = '=';
        }
        $this->havings[] = \compact('type', 'column', 'operator', 'value', 'boolean');
        if (!($value instanceof Expression)) {
            $this->addBinding($value, 'having');
        }
        return $this;
    }
    public function orHaving(string $column, ?string $operator = null, mixed $value = null): static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    // --- OrderBy / Limit / Offset ---
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = \strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Order direction must be "ASC" or "DESC".');
        }
        $this->orders[] = \compact('column', 'direction');
        return $this;
    }
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $value): static
    {
        if ($value >= 0) {
            $this->limit = $value;
        }
        return $this;
    }
    public function offset(int $value): static
    {
        if ($value >= 0) {
            $this->offset = $value;
        }
        return $this;
    }
    public function take(int $value): static { return $this->limit($value); }
    public function skip(int $value): static { return $this->offset($value); }

    // --- Aggregates ---
    protected function aggregate(string $function, array|string $columns = ['*']): mixed
    {
        $this->aggregate = \compact('function', 'columns');
        $previousColumns = $this->columns;
        $this->columns = [];

        $result = $this->get();

        $this->aggregate = null;
        $this->columns = $previousColumns;

        if ($result instanceof Collection && !$result->isEmpty()) {
            $row = $result->first();
            return \is_array($row) ? ($row['aggregate'] ?? null) : ($row->aggregate ?? null);
        }
        return null;
    }
    public function count(string $columns = '*'): int { return (int) $this->aggregate(__FUNCTION__, (array)$columns); }
    public function max(string $column): mixed { return $this->aggregate(__FUNCTION__, [$column]); }
    public function min(string $column): mixed { return $this->aggregate(__FUNCTION__, [$column]); }
    public function avg(string $column): mixed { return $this->aggregate(__FUNCTION__, [$column]); }
    public function sum(string $column): mixed { return $this->aggregate(__FUNCTION__, [$column]); }

    public function exists(): bool
    {
        $originalColumns = $this->columns;
        $this->columns = [DB::raw('1 as `exists`')]; // Select raw 1 with an alias
        $this->limit(1);
        $result = $this->get();
        $this->columns = $originalColumns;
        $this->limit = null;
        return !$result->isEmpty();
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }


    // --- Execution Methods ---
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    public function getBindings(bool $flatten = true): array
    {
        if (!$flatten) {
            return $this->bindings;
        }
        $flatBindings = [];
        foreach (['select', 'join', 'where', 'having'] as $type) {
            if (isset($this->bindings[$type])) {
                foreach ($this->bindings[$type] as $binding) {
                    if (is_array($binding)) {
                        $flatBindings = array_merge($flatBindings, $binding);
                    } else {
                        $flatBindings[] = $binding;
                    }
                }
            }
        }
        // Handle bindings from nested queries within 'wheres'
        foreach ($this->wheres as $where) {
            if (isset($where['query']) && $where['query'] instanceof Builder) {
                $flatBindings = array_merge($flatBindings, $where['query']->getBindings());
            }
        }

        return $flatBindings;
    }

    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                \is_string($key) ? $key : $key + 1,
                $value,
                $this->getPdoType($value)
            );
        }
    }

    protected function getPdoType(mixed $value): int
    {
        return match (\gettype($value)) {
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'NULL' => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    public function get(array|string $columns = ['*']): Collection
    {
        if ($columns !== ['*'] || (\is_array($columns) && ($this->columns === null || $columns[0] !== '*'))) {
            if ($this->columns === null && $columns === ['*']) {
            } else {
                $this->select($columns);
            }
        } else if ($this->columns === null) {
            $this->select(['*']);
        }

        $sql = $this->toSql();
        $bindings = $this->getBindings();

        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $bindings);
        $statement->execute();
        $results = $statement->fetchAll();

        $models = $this->model ? $this->hydrate($results) : new Collection(array_map(fn($item) => (object) $item, $results));

        if ($this->model && !$models->isEmpty() && !empty($this->eagerLoad)) {
            $this->eagerLoadRelations($models->all());
        }
        $this->fresh();
        return $models;
    }

    public function first(array|string $columns = ['*']): mixed
    {
        $originalLimit = $this->limit;
        $this->limit(1);
        $collection = $this->get($columns);
        $this->limit = $originalLimit;
        return $collection->first();
    }

    public function find(mixed $id, array|string $columns = ['*']): ?Model
    {
        if (\is_null($this->model)) {
            throw new \RuntimeException("Find method requires a model context.");
        }
        return $this->where($this->model->getKeyName(), '=', $id)->first($columns);
    }

    public function value(string $column): mixed
    {
        $result = $this->first([$column]);
        if ($result) {
            return $this->model ? $result->{$column} : ($result[$column] ?? null);
        }
        return null;
    }

    public function pluck(string $column, ?string $key = null): Collection
    {
        $columnsToSelect = \is_null($key) ? [$column] : [$column, $key];
        $results = $this->get($columnsToSelect);

        $plucked = [];
        if (\is_null($key)) {
            foreach ($results as $row) {
                $plucked[] = $this->model ? $row->{$column} : $row->{$column};
            }
        } else {
            foreach ($results as $row) {
                $plucked[$this->model ? $row->{$key} : $row->{$key}] = $this->model ? $row->{$column} : $row->{$column};
            }
        }
        return new Collection($plucked);
    }


    protected function hydrate(array $items): Collection
    {
        $instances = [];
        if (!$this->model) {
            return new Collection($items);
        }
        foreach ($items as $item) {
            $instances[] = $this->model->newInstance((array)$item, true);
        }
        return new Collection($instances);
    }

    public function with(string|array $relations): static
    {
        $relations = \is_string($relations) ? \func_get_args() : $relations;
        $this->eagerLoad = \array_merge($this->eagerLoad, (array) $relations);
        return $this;
    }

    public function eagerLoadRelations(array $models): void
    {
        if (empty($models) || empty($this->eagerLoad) || !$this->model) {
            return;
        }

        foreach ($this->parseRelations($this->eagerLoad) as $name => $constraints) {
            if (!\method_exists($this->model, $name)) {
                throw new \RuntimeException("Relation method [{$name}] not found on model [" . \get_class($this->model) . "].");
            }

            $relation = $this->model->newInstance()->$name();
            if (!$relation instanceof Relation) {
                throw new \RuntimeException("Method [{$name}] on model [" . \get_class($this->model) . "] did not return a Relation object.");
            }

            if ($constraints instanceof Closure) {
                $constraints($relation->getQuery());
            }

            $relation->addEagerConstraints($models);
            $relation->initRelation($models, $name);

            $relatedResults = $relation->getResults();
            if (!($relatedResults instanceof Collection)) {
                $relatedResults = new Collection($relatedResults ? [$relatedResults] : []);
            }

            $relation->match($models, $relatedResults, $name);
        }
    }

    protected function parseRelations(array $relationsInput): array
    {
        $parsed = [];
        foreach ($relationsInput as $name => $constraints) {
            if (\is_numeric($name)) {
                $name = $constraints;
                $constraints = function () {};
            }
            if (\str_contains($name, '.')) {
                $this->parseNestedRelation($name, $constraints, $parsed);
            } else {
                $parsed[$name] = $constraints;
            }
        }
        return $parsed;
    }

    protected function parseNestedRelation(string $name, Closure $constraints, array &$results): void
    {
        [$relation, $nested] = \explode('.', $name, 2);

        if (!isset($results[$relation]) || !($results[$relation] instanceof Closure)) {
            $results[$relation] = function ($query) use ($nested, $constraints) {
                $query->with([$nested => $constraints]);
            };
        } else {
            $existingConstraints = $results[$relation];
            $results[$relation] = function ($query) use ($existingConstraints, $nested, $constraints) {
                $existingConstraints($query);
                $query->with([$nested => $constraints]);
            };
        }
    }


    // --- Insert, Update, Delete ---
    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }
        if (!\is_array(reset($values))) {
            $values = [$values];
        }
        $bindings = [];
        foreach ($values as $record) {
            foreach (\array_values($record) as $value) {
                $bindings[] = $value;
            }
        }
        $sql = $this->grammar->compileInsert($this, $values);

        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $bindings);
        return $statement->execute();
    }

    public function insertGetId(array $values, ?string $sequence = null): int|string|false
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, \array_values($values));
        $executed = $statement->execute();

        if (!$executed) {
            return false;
        }

        $id = $this->pdo->lastInsertId($sequence);
        return $id ?: false;
    }

    public function update(array $values): int
    {
        $allBindings = \array_merge(\array_values($values), $this->getBindings(false)['where'] ?? []);
        $sql = $this->grammar->compileUpdate($this, $values);

        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $allBindings);
        $statement->execute();
        return $statement->rowCount();
    }

    public function delete(mixed $id = null): int
    {
        if (!\is_null($id)) {
            if (\is_null($this->model)) {
                throw new \RuntimeException("Delete with ID requires a model context to know the key name.");
            }
            $this->where($this->model->getKeyName(), '=', $id);
        }
        $sql = $this->grammar->compileDelete($this);

        $actualBindingsForDelete = $this->bindings['where'] ?? [];

        $statement = $this->pdo->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException("Failed to prepare SQL statement for delete: " . $sql);
        }
        $this->bindValues($statement, $actualBindingsForDelete);
        $statement->execute();
        return $statement->rowCount();
    }

    public function truncate(): void
    {
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $statement = $this->pdo->prepare($sql);
            $this->bindValues($statement, $bindings);
            $statement->execute();
        }
    }

    // --- Helper Methods ---
    public function addBinding(mixed $value, string $type = 'where'): static
    {
        if (!\array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}. Valid types: " . \implode(', ', \array_keys($this->bindings)));
        }

        if (\is_array($value)) {
            foreach ($value as $individualValue) {
                $this->bindings[$type][] = $individualValue;
            }
        } else {
            $this->bindings[$type][] = $value;
        }
        return $this;
    }

    public function newQuery(): static
    {
        return new static($this->pdo, $this->grammar, $this->dbManager);
    }

    public function clone(): static
    {
        $clone = clone $this;

        // Deep clone nested query builders within 'wheres'
        foreach ($clone->wheres as $key => $where) {
            if (isset($where['query']) && $where['query'] instanceof self) {
                $clone->wheres[$key]['query'] = clone $where['query'];
            }
        }

        // Deep clone bindings to ensure they are independent
        $clone->bindings = array_map(function ($bindingType) {
            return is_array($bindingType) ? $bindingType : [$bindingType];
        }, $this->bindings);

        return $clone;
    }

    protected function fresh(): static
    {
        $this->aggregate = null;
        $this->columns = null;
        $this->joins = [];
        $this->wheres = [];
        $this->groups = null;
        $this->havings = null;
        $this->orders = null;
        $this->limit = null;
        $this->offset = null;
        $this->eagerLoad = [];
        $this->bindings = [
            'select' => [], 'from'   => [], 'join'   => [],
            'where'  => [], 'having' => [], 'order'  => [],
        ];
        return $this;
    }

    public function dd(): void
    {
        \var_dump($this->toSql());
        \var_dump($this->getBindings());
        die(1);
    }

    public function dump(): static
    {
        \var_dump($this->toSql());
        \var_dump($this->getBindings());
        return $this;
    }

    // --- Transaction Proxies ---

    /**
     * @throws Throwable
     */
    public function transaction(Closure $callback, int $attempts = 1)
    {
        return $this->dbManager->transaction($callback, $attempts);
    }
    public function beginTransaction(): void
    { $this->dbManager->beginTransaction(); }
    public function commit(): void
    { $this->dbManager->commit(); }
    public function rollBack(): void
    { $this->dbManager->rollBack(); }

    /**
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $page = $page ?: (int) ($_GET[$pageName] ?? 1);

        // Clone the current builder instance to get the total count
        // without the limit and offset applied for the current page.
        $totalQuery = $this->clone();
        $totalQuery->limit = null; // Ensure no limit is applied for count
        $totalQuery->offset = null; // Ensure no offset is applied for count

        $total = $totalQuery->count();

        $results = $this->forPage($page, $perPage)->get($columns);

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => \request()->path(),
                'query' => \request()->query(), // Pass all current query parameters
                'pageName' => $pageName,
            ]
        );
    }

    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    public function whereHas(string $relationName, ?Closure $callback = null, string $operator = '>=', int $count = 1): static
    {
        if (! $this->model || !\method_exists($this->model, $relationName)) {
            throw new InvalidArgumentException("Invalid relation name provided to whereHas or model context not set.");
        }
        throw new \BadMethodCallException('whereHas is not fully implemented yet.');
    }

}




