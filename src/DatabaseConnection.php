<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use HbLib\DBAL\Driver\DriverInterface;
use LogicException;
use PDO;
use PDOStatement;

class DatabaseConnection implements DatabaseConnectionInterface
{
    public function __construct(
        private PDO $pdo,
        private DriverInterface $driver,
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastInsertId(?string $name = null): string
    {
        $id = $this->pdo->lastInsertId($name);

        if ($id === false) {
            throw new Exception\DBALException('Unable to get the insert id');
        }

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query): PDOStatement
    {
        $query = $this->pdo->query($query);

        if ($query === false) {
            throw new LogicException('Query is false');
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $query): PDOStatement
    {
        $stmt = $this->pdo->prepare($query);

        return $stmt;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }
}
