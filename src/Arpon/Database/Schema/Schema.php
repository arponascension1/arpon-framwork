<?php

namespace Arpon\Database\Schema;

use Arpon\Database\DatabaseManager;
use Arpon\Database\Query\Grammars\MySqlGrammar;
use Arpon\Database\Query\Grammars\SQLiteGrammar;
use InvalidArgumentException;

class Schema
{
    protected static ?DatabaseManager $db = null;

    public static function setDatabaseManager(DatabaseManager $dbManager): void
    {
        static::$db = $dbManager;
    }

    protected static function getDatabaseManager(): DatabaseManager
    {
        if (static::$db === null) {
            throw new \RuntimeException("DatabaseManager not set for Schema. Call Schema::setDatabaseManager() first.");
        }
        return static::$db;
    }

    public static function create(string $table, \Closure $callback): void
    {
        if (static::hasTable($table)) {
            return; // Table already exists, do nothing
        }

        $blueprint = static::getBlueprint($table);
        $callback($blueprint);
        static::executeBlueprint($blueprint, 'create');
    }

    public static function dropIfExists(string $table): void
    {
        $blueprint = static::getBlueprint($table);
        $blueprint->dropIfExists();
        static::executeBlueprint($blueprint, 'dropIfExists');
    }

    public static function table(string $table, \Closure $callback): void
    {
        if (! static::hasTable($table)) {
            throw new \RuntimeException("Table [{$table}] does not exist.");
        }
        $blueprint = static::getBlueprint($table);
        $callback($blueprint);
        static::executeBlueprint($blueprint, 'alter');
    }

    protected static function getBlueprint(string $table): Blueprint
    {
        $db = static::getDatabaseManager();
        $driver = strtolower($db->config['connections'][$db->getDefaultConnectionName()]['driver']);

        $grammar = match ($driver) {
            'mysql' => new MySqlGrammar(),
            'sqlite' => new SQLiteGrammar(),
            default => throw new InvalidArgumentException("Unsupported database driver for schema: {$driver}"),
        };

        return new Blueprint($table, $grammar);
    }

    protected static function executeBlueprint(Blueprint $blueprint, string $method): void
    {
        $db = static::getDatabaseManager();
        $statements = $blueprint->toSql($method);

        foreach ($statements as $statement) {
            $db->statement($statement);
        }
    }

    public static function hasTable(string $table): bool
    {
        $db = static::getDatabaseManager();
        $driver = strtolower($db->config['connections'][$db->getDefaultConnectionName()]['driver']);

        // This is a simplified check. Real implementations might query information_schema.
        // For SQLite, we can query sqlite_master.
        if ($driver === 'sqlite') {
            $result = $db->table('sqlite_master')
                         ->where('type', 'table')
                         ->where('name', $table)
                         ->first();
            return !empty($result);
        }

        // For other databases, a simple select might work if the table exists,
        // but a more robust solution would query information_schema.
        try {
            $db->table($table)->limit(1)->get();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
