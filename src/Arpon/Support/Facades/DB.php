<?php

namespace Arpon\Support\Facades;

use Arpon\Database\Query\Builder;
use Arpon\Database\Query\Grammars\Grammar;

/**
 * @method static Builder table(string $table, ?string $connectionName = null)
 * @method static \PDO pdoConnection(?string $name = null)
 * @method static Builder query(?string $connectionName = null)
 * @method static array select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static mixed selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static int insert(string $query, array $bindings = [])
 * @method static int update(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static bool statement(string $query, array $bindings = [])
 * @method static mixed transaction(\Closure $callback, int $attempts = 1)
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack(int $toLevel = null)
 * @method static string getDefaultConnectionName()
 * @method static void setDefaultConnectionName(string $name)
 * @method static Grammar getGrammar(?string $connectionName = null)
 */
class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}
