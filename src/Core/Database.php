<?php

namespace Bazaar\Core;

use PDO;
use PDOStatement;

final class Database
{
    private static ?Database $instance = null;

    private function __construct(private readonly PDO $pdo)
    {
    }

    public static function instance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self(self::connect());
        }
        return self::$instance;
    }

    public static function swap(Database $database): void
    {
        self::$instance = $database;
    }

    public static function fromPdo(PDO $pdo): Database
    {
        return new self($pdo);
    }

    private static function connect(): PDO
    {
        $driver = (string) Config::get('DB_DRIVER', 'sqlite');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'sqlite') {
            $path = (string) Config::get('DB_DATABASE');
            if ($path !== ':memory:' && !str_starts_with($path, '/')) {
                $path = basePath($path);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            return $pdo;
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $driver,
            Config::get('DB_HOST'),
            Config::get('DB_PORT'),
            Config::get('DB_DATABASE')
        );
        return new PDO($dsn, (string) Config::get('DB_USERNAME'), (string) Config::get('DB_PASSWORD'), $options);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    private function run(string $sql, array $bindings): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        return $statement;
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings)->fetchAll();
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->run($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        return $this->run($sql, $bindings)->fetchColumn();
    }

    public function statement(string $sql, array $bindings = []): int
    {
        return $this->run($sql, $bindings)->rowCount();
    }

    public function insert(string $sql, array $bindings = []): int
    {
        $this->run($sql, $bindings);
        return (int) $this->pdo->lastInsertId();
    }

    public function transaction(callable $work): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $outcome = $work($this);
            $this->pdo->commit();
            return $outcome;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }
}
