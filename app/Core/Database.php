<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Thin PDO wrapper. Every query goes through prepared statements.
 *
 * The production target is MySQL/MariaDB. SQLite is supported only so the
 * automated test suite can run without a MySQL server.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(array $config): void
    {
        self::$driver = $config['driver'];
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if (self::$driver === 'sqlite') {
            self::$pdo = new PDO('sqlite:' . $config['database'], null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );
        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        // Store and compare timestamps in Malaysia time.
        self::$pdo->exec("SET time_zone = '+08:00'");
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database is not connected.');
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    /** Row-lock clause for SELECTs inside a transaction (MySQL only; SQLite locks the whole DB). */
    public static function forUpdate(): string
    {
        return self::$driver === 'mysql' ? ' FOR UPDATE' : '';
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : (str_starts_with($key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, is_bool($value) ? (int) $value : $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** Insert a row. Column names come from code, never from user input. */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', array_map(fn ($c) => ':' . $c, $columns))
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    /** Update rows. $where is a fixed SQL fragment with named placeholders. */
    public static function update(string $table, array $data, string $where, array $whereParams): int
    {
        $sets = implode(', ', array_map(fn ($c) => "$c = :set_$c", array_keys($data)));
        $params = [];
        foreach ($data as $column => $value) {
            $params['set_' . $column] = $value;
        }
        $stmt = self::query("UPDATE $table SET $sets WHERE $where", $params + $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Run $callback inside a transaction. Any exception rolls back and is re-thrown.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
