<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use LogicException;
use PDO;
use PDOStatement;

class DatabaseConnection implements DatabaseConnectionInterface
{
    public function __construct(
        private PDO $pdo
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getLastInsertId(): string
    {
        return $this->pdo->lastInsertId();
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

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }
}
