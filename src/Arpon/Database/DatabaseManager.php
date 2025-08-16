<?php

namespace Arpon\Database;

use Closure;
use Arpon\Database\Connection\ConnectionFactory;
use Arpon\Database\Query\Builder as QueryBuilder;
use Arpon\Database\Query\Grammars\Grammar;
use Arpon\Database\Query\Grammars\MySqlGrammar;
use Arpon\Database\Query\Grammars\SQLiteGrammar;
use Arpon\Database\Schema\Schema;
use InvalidArgumentException;
use PDO;

class DatabaseManager
{
    public array $config = [];
    protected ConnectionFactory $factory;

    /**
     * The active PDO connections.
     * @var PDO[]
     */
    protected array $connections = [];

    /**
     * The reconnector instances for each connection.
     * @var callable[]
     */
    protected array $reconnectors = [];


    protected string $defaultConnectionName = 'default'; // Default key, actual name resolved from config

    public function __construct(array $config, ?ConnectionFactory $factory = null)
    {
        $this->config = $config;
        $this->factory = $factory ?? new ConnectionFactory();

        if (isset($config['default']) && is_string($config['default'])) {
            $this->defaultConnectionName = $config['default'];
        } elseif (!empty($config['connections'])) {
            // Fallback to the first defined connection if 'default' is not set
            $this->defaultConnectionName = array_key_first($config['connections']);
        } else {
            throw new InvalidArgumentException("No database connections configured and no default connection specified.");
        }
    }

    /**
     * Get a database PDO connection instance.
     *
     * @param string|null $name The name of the connection (null for default).
     * @return PDO
     * @throws InvalidArgumentException If the connection is not configured.
     */
    public function pdoConnection(?string $name = null): PDO
    {
        $name = $name ?? $this->getDefaultConnectionName();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
            $this->reconnectors[$name] = function() use ($name) {
                unset($this->connections[$name]); // Disconnect
                return $this->pdoConnection($name); // Reconnect
            };
        }

        return $this->connections[$name];
    }

    /**
     * Make the PDO connection instance.
     */
    protected function makeConnection(string $name): PDO
    {
        if (!isset($this->config['connections'][$name])) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }
        $connectionConfig = $this->config['connections'][$name];
        return $this->factory->make($connectionConfig);
    }


    /**
     * Get the default connection name.
     */
    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnectionName;
    }

    /**
     * Set the default connection name.
     */
    public function setDefaultConnectionName(string $name): void
    {
        if (!isset($this->config['connections'][$name])) {
            throw new InvalidArgumentException("Cannot set default connection to [{$name}] because it is not configured.");
        }
        $this->defaultConnectionName = $name;
    }

    /**
     * Get a Query Builder instance for a specific table on the specified connection.
     *
     * @param string $table The database table name.
     * @param string|null $connectionName The name of the connection (null for default).
     * @return QueryBuilder
     */
    public function table(string $table, ?string $connectionName = null): QueryBuilder
    {
        return $this->query($connectionName)->table($table);
    }

    /**
     * Get a new Query Builder instance for the specified connection.
     *
     * @param string|null $connectionName The name of the connection (null for default).
     * @return QueryBuilder
     */
    public function query(?string $connectionName = null): QueryBuilder
    {
        $connectionName = $connectionName ?? $this->getDefaultConnectionName();
        $pdo = $this->pdoConnection($connectionName);
        $grammar = $this->resolveGrammar($connectionName);

        return new QueryBuilder($pdo, $grammar, $this); // Pass DatabaseManager for transactions
    }

    public function schema(?string $connectionName = null): Schema
    {
        $connectionName = $connectionName ?? $this->getDefaultConnectionName();
        $pdo = $this->pdoConnection($connectionName);
        $grammar = $this->resolveGrammar($connectionName);

        return new Schema($pdo, $grammar);
    }

    /**
     * Resolve the query grammar for a connection.
     */
    public function resolveGrammar(?string $connectionName = null): Grammar
    {
        $connectionName = $connectionName ?? $this->getDefaultConnectionName();
        if (!isset($this->config['connections'][$connectionName]['driver'])) {
            throw new InvalidArgumentException("Driver not configured for connection [{$connectionName}].");
        }
        $driver = strtolower($this->config['connections'][$connectionName]['driver']);

        return match ($driver) {
            'mysql' => new MySqlGrammar(),
            'sqlite' => new SQLiteGrammar(), // You'll need to create this
            // 'pgsql' => new PostgresGrammar(),
            // 'sqlsrv' => new SqlServerGrammar(),
            default => throw new InvalidArgumentException("Unsupported database driver for grammar: {$driver}"),
        };
    }

    /**
     * Execute a SELECT statement and return an array of results.
     */
    public function select(string $query, array $bindings = [], bool $useReadPdo = true, ?string $connectionName = null): array
    {
        // For simplicity, useReadPdo is ignored here. A full implementation might have read/write connections.
        $pdo = $this->pdoConnection($connectionName);
        error_log("Failing Query: " . $query);
        error_log("Failing Bindings: " . print_r($bindings, true));
        $statement = $pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->fetchAll();
    }

    /**
     * Execute a SELECT statement and return a single result.
     */
    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true, ?string $connectionName = null): mixed
    {
        $records = $this->select($query, $bindings, $useReadPdo, $connectionName);
        return array_shift($records) ?: null;
    }

    /**
     * Execute an INSERT statement.
     */
    public function insert(string $query, array $bindings = [], ?string $connectionName = null): bool
    {
        return $this->statement($query, $bindings, $connectionName);
    }

    /**
     * Execute an UPDATE statement.
     */
    public function update(string $query, array $bindings = [], ?string $connectionName = null): int
    {
        $pdo = $this->pdoConnection($connectionName);
        $statement = $pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    /**
     * Execute a DELETE statement.
     */
    public function delete(string $query, array $bindings = [], ?string $connectionName = null): int
    {
        return $this->update($query, $bindings, $connectionName); // Same logic as update for rowCount
    }

    /**
     * Execute an SQL statement and return the boolean result.
     */
    public function statement(string $query, array $bindings = [], ?string $connectionName = null): bool
    {
        $pdo = $this->pdoConnection($connectionName);
        $statement = $pdo->prepare($query);
        return $statement->execute($bindings);
    }

    /**
     * Execute a Closure within a database transaction.
     *
     * @param Closure $callback
     * @param int $attempts Number of times to attempt the transaction on deadlock.
     * @param string|null $connectionName
     * @return mixed The result of the callback.
     * @throws \Throwable If the callback throws an exception or transaction fails.
     */
    public function transaction(Closure $callback, int $attempts = 1, ?string $connectionName = null): mixed
    {
        $pdo = $this->pdoConnection($connectionName);
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $pdo->beginTransaction();
            try {
                $result = $callback($this); // Pass $this (DatabaseManager) or specific connection
                $pdo->commit();
                return $result;
            } catch (\Throwable $e) {
                $pdo->rollBack();
                // Check for deadlock or specific transaction-related exceptions if needed for retries
                if ($currentAttempt < $attempts /* && $this->causedByDeadlock($e) */) {
                    // You might want a small delay before retrying
                    // usleep(250000); // 0.25 seconds
                    continue;
                }
                throw $e; // Re-throw after final attempt or non-retryable error
            }
        }
        // Should not be reached if attempts > 0
        throw new \RuntimeException("Transaction failed after {$attempts} attempts.");
    }

    public function beginTransaction(?string $connectionName = null): void
    {
        $this->pdoConnection($connectionName)->beginTransaction();
    }

    public function commit(?string $connectionName = null): void
    {
        $this->pdoConnection($connectionName)->commit();
    }

    public function rollBack(?int $toLevel = null, ?string $connectionName = null): void
    {
        // PDO's rollBack doesn't take a level argument like some DBs.
        // For simplicity, we ignore $toLevel or throw if it's used.
        if (!is_null($toLevel)) {
            // Log warning or throw, as PDO doesn't support savepoint rollback directly via level
        }
        $this->pdoConnection($connectionName)->rollBack();
    }

    /**
     * Dynamically pass methods to a new query builder instance for the default connection.
     * This allows for DB::select(...), DB::insert(...) etc. via the Facade.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->query()->$method(...$parameters);
    }
}
