<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use HbLib\DBAL\Driver\DriverInterface;
use PDOStatement;

interface DatabaseConnectionInterface
{
    public function getDriver(): DriverInterface;

    /**
     * Execute a SQL query.
     *
     * @param string $query
     * @return PDOStatement<mixed>
     */
    public function query(string $query): PDOStatement;

    /**
     * Create a prepared statement.
     *
     * @param string $query
     * @return PDOStatement<mixed>
     */
    public function prepare(string $query): PDOStatement;

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     * @throws Exception\DBALException When \PDO::lastInsertId returns false.
     * @return string
     */
    public function getLastInsertId(?string $name = null): string;

    public function beginTransaction(): bool;
    public function inTransaction(): bool;
    public function rollBack(): bool;
    public function commit(): bool;
}
