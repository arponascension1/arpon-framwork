<?php

namespace Arpon\Database\Connection;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class ConnectionFactory
{
    /**
     * Create a new PDO connection instance based on the configuration.
     *
     * @param array $config The database connection configuration.
     * @return PDO The PDO connection instance.
     * @throws InvalidArgumentException If the driver is not specified or unsupported.
     * @throws RuntimeException If connection fails.
     */
    public function make(array $config): PDO
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException("Database driver not specified in connection configuration.");
        }

        $driver = strtolower($config['driver']);

        return match ($driver) {
            'mysql' => $this->createMySqlPdoConnection($config),
            'sqlite' => $this->createSQLitePdoConnection($config),
            // 'pgsql' => $this->createPostgresPdoConnection($config), // Example for future
            // 'sqlsrv' => $this->createSqlServerPdoConnection($config), // Example for future
            default => throw new InvalidArgumentException("Unsupported database driver: {$config['driver']}"),
        };
    }

    /**
     * Create a PDO connection for MySQL.
     */
    protected function createMySqlPdoConnection(array $config): PDO
    {
        $dsn = $this->buildMySqlDsn($config);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $options = $config['options'] ?? [];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);

            // Set common attributes, can be overridden by config['options']
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Default to assoc for convenience
            if (!empty($config['charset'])) {
                // Already handled by DSN for modern PDO, but can be set via MYSQL_ATTR_INIT_COMMAND too
                // $pdo->exec("SET NAMES '{$config['charset']}'" . (!empty($config['collation']) ? " COLLATE '{$config['collation']}'" : ''));
            }


            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException("Could not connect to MySQL database: " . $e->getMessage() . " (DSN: {$dsn})", (int)$e->getCode(), $e);
        }
    }

    /**
     * Build DSN string for MySQL.
     */
    protected function buildMySqlDsn(array $config): string
    {
        $dsnParts = [];
        if (isset($config['host'])) {
            $dsnParts[] = "host={$config['host']}";
        }
        if (isset($config['port'])) {
            $dsnParts[] = "port={$config['port']}";
        }
        if (isset($config['database'])) {
            $dsnParts[] = "dbname={$config['database']}";
        }
        if (isset($config['charset'])) {
            $dsnParts[] = "charset={$config['charset']}";
        }
        // Example for unix_socket:
        // if (isset($config['unix_socket'])) {
        //     $dsnParts[] = "unix_socket={$config['unix_socket']}";
        // }
        return 'mysql:' . implode(';', $dsnParts);
    }

    /**
     * Create a PDO connection for SQLite.
     */
    protected function createSQLitePdoConnection(array $config): PDO
    {
        if (empty($config['database'])) {
            throw new InvalidArgumentException("SQLite database path not specified.");
        }
        $dsn = "sqlite:{$config['database']}";
        $options = $config['options'] ?? [];

        try {
            $pdo = new PDO($dsn, null, null, $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // SQLite specific pragmas can be set here if needed, e.g., foreign_keys
            // if (isset($config['foreign_keys']) && $config['foreign_keys']) {
            //     $pdo->exec('PRAGMA foreign_keys = ON;');
            // }
            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException("Could not connect to SQLite database: " . $e->getMessage() . " (Path: {$config['database']})", (int)$e->getCode(), $e);
        }
    }

    // Placeholder for PostgreSQL
    // protected function createPostgresPdoConnection(array $config): PDO { /* ... */ }

    // Placeholder for SQL Server
    // protected function createSqlServerPdoConnection(array $config): PDO { /* ... */ }
}
