<?php
declare(strict_types=1);

namespace HbLib\DBAL;

use HbLib\DBAL\Driver\DriverInterface;
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

    public function getDriver(): DriverInterface
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->getDriver();
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query): PDOStatement
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->query($query);
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $query): PDOStatement
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->prepare($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastInsertId(?string $name = null): string
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->getLastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->beginTransaction();
    }

    public function rollBack(): bool
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->rollBack();
    }

    public function commit(): bool
    {
        return ($this->dbConnection ??= ($this->dbConnectionFactory)())->commit();
    }
}
