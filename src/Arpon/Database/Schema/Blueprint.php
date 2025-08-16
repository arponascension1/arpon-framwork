<?php

namespace Arpon\Database\Schema;

use Arpon\Database\Query\Grammars\Grammar;

class Blueprint
{
    protected string $table;
    protected Grammar $grammar;
    protected array $columns = [];
    protected array $commands = [];

    public function __construct(string $table, Grammar $grammar)
    {
        $this->table = $table;
        $this->grammar = $grammar;
    }

    public function id(): ColumnDefinition
    {
        return $this->increments('id');
    }

    public function increments(string $column): ColumnDefinition
    {
        return $this->addColumn('increments', $column);
    }

    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('string', $column, compact('length'));
    }

    public function unsignedBigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedBigInteger', $column);
    }

    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column);
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn('dateTime', $column);
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn('json', $column);
    }

    public function double(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn('double', $column, compact('total', 'places'));
    }

    public function float(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn('float', $column, compact('total', 'places'));
    }

    public function uuid(string $column = 'uuid'): ColumnDefinition
    {
        return $this->addColumn('uuid', $column);
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn('longText', $column);
    }

    public function mediumText(string $column): ColumnDefinition
    {
        return $this->addColumn('mediumText', $column);
    }

    public function tinyInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('tinyInteger', $column);
    }

    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('smallInteger', $column);
    }

    public function mediumInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('mediumInteger', $column);
    }

    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    public function dropRememberToken(): void
    {
        $this->dropColumn('remember_token');
    }

    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column);
    }

    public function decimal(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    public function unsignedDecimal(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn('unsignedDecimal', $column, compact('total', 'places'));
    }

    public function char(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    public function ipAddress(string $column = 'ip_address'): ColumnDefinition
    {
        return $this->addColumn('ipAddress', $column);
    }

    public function macAddress(string $column = 'mac_address'): ColumnDefinition
    {
        return $this->addColumn('macAddress', $column);
    }

    public function tinyText(string $column): ColumnDefinition
    {
        return $this->addColumn('tinyText', $column);
    }

    public function unsignedInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedInteger', $column);
    }

    public function unsignedTinyInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedTinyInteger', $column);
    }

    public function unsignedSmallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedSmallInteger', $column);
    }

    public function unsignedMediumInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedMediumInteger', $column);
    }

    public function jsonb(string $column): ColumnDefinition
    {
        return $this->addColumn('jsonb', $column);
    }

    public function foreignId(string $column): ColumnDefinition
    {
        return $this->addColumn('foreignId', $column);
    }

    public function foreign(string $column): ForeignKeyDefinition
    {
        $foreign = new ForeignKeyDefinition($this, $column);
        $this->commands[] = $foreign;
        return $foreign;
    }

    public function index(array|string $columns, string $name = null): void
    {
        $columns = (array) $columns;
        $this->commands[] = ['name' => 'index', 'columns' => $columns, 'index_name' => $name];
    }

    public function primary(array|string $columns, string $name = null): void
    {
        $columns = (array) $columns;
        $this->commands[] = ['name' => 'primary', 'columns' => $columns, 'index_name' => $name];
    }

    public function unique(array|string $columns, string $name = null): void
    {
        $columns = (array) $columns;
        $this->commands[] = ['name' => 'unique', 'columns' => $columns, 'index_name' => $name];
    }

    public function foreignIdFor(string $model, string $column = null): ColumnDefinition
    {
        // This is a simplified implementation. A full implementation would resolve the model to a table and column.
        $column = $column ?: strtolower(class_basename($model)) . '_id';
        return $this->addColumn('unsignedBigInteger', $column);
    }

    public function dropIfExists(): void
    {
        $this->commands[] = ['name' => 'dropIfExists'];
    }

    public function drop(): void
    {
        $this->commands[] = ['name' => 'drop'];
    }

    public function rename(string $newName): void
    {
        $this->commands[] = ['name' => 'renameTable', 'to' => $newName];
    }

    public function dropColumn(string $column): void
    {
        $this->commands[] = ['name' => 'dropColumn', 'columns' => [$column]];
    }

    public function dropColumns(array $columns): void
    {
        $this->commands[] = ['name' => 'dropColumn', 'columns' => $columns];
    }

    public function renameColumn(string $from, string $to): void
    {
        $this->commands[] = ['name' => 'renameColumn', 'from' => $from, 'to' => $to];
    }

    public function addCommand(array $command): void
    {
        $this->commands[] = $command;
    }

    protected function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        $column = new ColumnDefinition(compact('type', 'name', 'parameters'));
        $this->columns[] = $column;
        return $column;
    }

    public function toSql(string $method): array
    {
        $statements = [];

        if ($method === 'create') {
            $statements[] = $this->grammar->compileCreate($this, $this->columns);
        } elseif ($method === 'alter') {
            $statements[] = $this->grammar->compileAlter($this, $this->columns);
        } elseif ($method === 'drop') {
            $statements[] = $this->grammar->compileDrop($this);
        } elseif ($method === 'dropIfExists') {
            $statements[] = $this->grammar->compileDropIfExists($this);
        }

        foreach ($this->commands as $command) {
            if ($command instanceof ForeignKeyDefinition) {
                $statements[] = $this->grammar->compileForeign($this, $command->toSql());
            } else if (is_array($command) && isset($command['name'])) {
                $methodName = 'compile' . ucfirst($command['name']);
                if (method_exists($this->grammar, $methodName)) {
                    $statements[] = $this->grammar->$methodName($this, $command);
                }
            }
        }

        return $statements;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
