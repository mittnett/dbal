<?php declare(strict_types=1);

namespace HbLib\DBAL\Tests;

use HbLib\DBAL\DatabaseConnection;
use HbLib\DBAL\DatabaseConnectionInterface;
use HbLib\DBAL\LazyDatabaseConnection;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class LazyDatabaseConnectionTest extends TestCase
{
    public function testFactoryNotCalledOnConstruct(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::never())->method('setAttribute');
        $pdo->expects(self::never())->method('query');

        $databaseConnection = new LazyDatabaseConnection(static fn (): DatabaseConnectionInterface => new DatabaseConnection($pdo));
    }

    public function testFactoryCalledOnQueryAndThenCached(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('setAttribute')->with(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->expects(self::exactly(2))->method('query')->with('SELECT u.id FROM users')->willReturn($this->createMock(PDOStatement::class));
        $pdo->expects(self::once())->method('commit')->willReturn(true);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('lastInsertId')->willReturn('2');
        $pdo->expects(self::once())->method('prepare')->with('SELECT u.id FROM users')->willReturn($this->createMock(PDOStatement::class));

        $databaseConnection = new LazyDatabaseConnection(static fn (): DatabaseConnectionInterface => new DatabaseConnection($pdo));
        $databaseConnection->query('SELECT u.id FROM users');
        $databaseConnection->query('SELECT u.id FROM users');

        $databaseConnection->commit();
        $databaseConnection->rollBack();
        $databaseConnection->beginTransaction();
        $databaseConnection->getLastInsertId();
        $databaseConnection->prepare('SELECT u.id FROM users');
    }
}
