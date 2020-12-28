<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use Closure;
use PDO;
use PDOStatement;

class LazyDatabaseConnection implements DatabaseConnectionInterface
{
    /**
     * @phpstan-var callable(): DatabaseConnectionInterface
     * @var callable
     */
    private $dbConnectionFactory;

    private ?DatabaseConnectionInterface $dbConnection;

    /**
     * @phpstan-param callable(): DatabaseConnectionInterface $dbConnectionFactory
     * @param callable $dbConnectionFactory
     */
    public function __construct(
        callable $dbConnectionFactory
    ) {
        $this->dbConnection = null;
        $this->dbConnectionFactory = $dbConnectionFactory;
    }

    public function __destruct()
    {
        if ($this->dbConnection !== null) {
            $this->dbConnection = null;
        }
    }

    /**
     * Calls the db connection factory to establish the database connection.
     */
    private function getOrMakeDatabaseConnection(): DatabaseConnectionInterface
    {
        $dbConnection = $this->dbConnection;

        if ($dbConnection === null) {
            $dbConnection = ($this->dbConnectionFactory)();
            $this->dbConnection = $dbConnection;
        }

        return $dbConnection;
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query): PDOStatement
    {
        return $this->getOrMakeDatabaseConnection()->query($query);
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $query): PDOStatement
    {
        return $this->getOrMakeDatabaseConnection()->prepare($query);
    }

    public function getLastInsertId(): string
    {
        return $this->getOrMakeDatabaseConnection()->getLastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->getOrMakeDatabaseConnection()->beginTransaction();
    }

    public function rollBack(): bool
    {
        return $this->getOrMakeDatabaseConnection()->rollBack();
    }

    public function commit(): bool
    {
        return $this->getOrMakeDatabaseConnection()->commit();
    }
}
