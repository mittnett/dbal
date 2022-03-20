<?php declare(strict_types=1);

namespace HbLib\DBAL\Tests;

use HbLib\DBAL\DatabaseConnection;
use HbLib\DBAL\Driver\MySQLDriver;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function testExceptionErrorMode(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('setAttribute')->with(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
    }

    public function testQuery(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('query')->with('SELECT u.id FROM users')->willReturn($this->createMock(PDOStatement::class));

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        self::assertInstanceOf(PDOStatement::class, $databaseConnection->query('SELECT u.id FROM users'));
    }

    public function testQueryIsFalse(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('query')->with('SELECT u.id FROM users')->willReturn(false);

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Query is false');

        $databaseConnection->query('SELECT u.id FROM users');
    }

    public function testPrepare(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('prepare')->with('SELECT u.id FROM users')->willReturn($this->createMock(PDOStatement::class));

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        self::assertInstanceOf(PDOStatement::class, $databaseConnection->prepare('SELECT u.id FROM users'));
    }

    public function testLastInsertId(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('lastInsertId')->willReturn('2');

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        self::assertSame('2', $databaseConnection->getLastInsertId());
    }

    public function testBeginTransaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        $databaseConnection->beginTransaction();
    }

    public function testRollback(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        $databaseConnection->rollBack();
    }

    public function testCommit(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('commit')->willReturn(true);

        $databaseConnection = new DatabaseConnection($pdo, new MySQLDriver());
        $databaseConnection->commit();
    }
}
