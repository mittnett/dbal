<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use PDOStatement;

interface DatabaseConnectionInterface
{
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

    public function getLastInsertId(): string;
    public function beginTransaction(): bool;
    public function rollBack(): bool;
    public function commit(): bool;
}
