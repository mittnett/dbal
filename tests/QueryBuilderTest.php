<?php declare(strict_types=1);

namespace HbLib\DBAL\Tests;

use HbLib\DBAL\DatabaseConnection;
use HbLib\DBAL\Driver\MySQLDriver;
use HbLib\DBAL\Query\AndX;
use HbLib\DBAL\Query\OrX;
use HbLib\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function range;

class QueryBuilderTest extends TestCase
{
    public function testSimpleSelect(): void
    {
        $qb = new QueryBuilder();
        $qb->addSelect('u.id');
        $qb->setFrom('users', 'u');
        $qb->addWhereCondition('u.id IN(:ids)');

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id IN(:ids))',
            actual: $qb->getSQL(),
        );

        $qb->setLimit(1);

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id IN(:ids)) LIMIT 1',
            actual: $qb->getSQL(),
        );

        $qb->setOffset(2);

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id IN(:ids)) LIMIT 2, 1',
            actual: $qb->getSQL(),
        );

        $qb->addOrderBy('u.id DESC');

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id IN(:ids)) ORDER BY u.id DESC LIMIT 2, 1',
            actual: $qb->getSQL(),
        );

        $qb->addGroupBy('u.group_id');

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id IN(:ids)) GROUP BY u.group_id ORDER BY u.id DESC LIMIT 2, 1',
            actual: $qb->getSQL(),
        );
    }

    public function testJoin(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addJoinCondition('INNER', 'user_groups', 'ug', ['ug.id = u.group_id']);

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u INNER JOIN user_groups AS ug ON (ug.id = u.group_id)',
            actual: $qb->getSQL(),
        );

        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addJoinCondition('INNER', 'user_groups', 'ug', ['ug.id = u.group_id']);

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u INNER JOIN user_groups AS ug ON (ug.id = u.group_id)',
            actual: $qb->getSQL(),
        );
    }

    public function testWhere(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.id = 1');
        $qb->addWhereCondition('u.id = 2');

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (u.id = 1 AND u.id = 2)',
            actual: $qb->getSQL(),
        );

        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition(new OrX(['u.id = 1', 'u.id = 2']));

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE ((u.id = 1 OR u.id = 2))',
            actual: $qb->getSQL(),
        );

        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition(new OrX(['u.username = :bob', 'u.id = 1']));
        $qb->addWhereCondition('u.active = 1');

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE ((u.username = :bob OR u.id = 1) AND u.active = 1)',
            actual: $qb->getSQL(),
        );

        $condition = new AndX([]);
        $condition->add(new OrX(['u.username = :bob', 'u.id = 1']));
        $condition->add('u.active = 1');

        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition($condition);

        self::assertSame(
            expected: 'SELECT u.id FROM users AS u WHERE (((u.username = :bob OR u.id = 1) AND u.active = 1))',
            actual: $qb->getSQL(),
        );
    }

    public function testBasicIntParameters(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.id = :id');
        $qb->setParameter('id', 1);

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects(self::once())->method('bindValue')->with(1, 1, PDO::PARAM_INT);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::once())->method('prepare')->with(
            'SELECT u.id FROM users AS u WHERE (u.id = ?)'
        )->willReturn($pdoStatementMock);

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());
        $stmt = $qb->createStatement($databaseConnection);
    }

    public function testBasicFloatParameters(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.number = :float');
        $qb->setParameter('float', 1.23456);

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects(self::exactly(1))->method('bindValue')->withConsecutive(
            [1, '1.23456', PDO::PARAM_STR],
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::once())->method('prepare')->with(
            'SELECT u.id FROM users AS u WHERE (u.number = ?)'
        )->willReturn($pdoStatementMock);

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());
        $qb->createStatement($databaseConnection);
    }

    public function testBasicBoolParameters(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.active = :yes');
        $qb->addWhereCondition('u.active = :no');
        $qb->setParameter('yes', true);
        $qb->setParameter('no', false);

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects(self::exactly(2))->method('bindValue')->withConsecutive(
            [1, 1, PDO::PARAM_INT],
            [2, 0, PDO::PARAM_INT],
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::once())->method('prepare')->with(
            'SELECT u.id FROM users AS u WHERE (u.active = ? AND u.active = ?)'
        )->willReturn($pdoStatementMock);

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());
        $qb->createStatement($databaseConnection);
    }

    public function testInParameterInts(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.id IN(:ids)');
        $qb->setParameter('ids', range(1, 5));

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects(self::exactly(5))->method('bindValue')->withConsecutive(
            [1, 1, PDO::PARAM_INT],
            [2, 2, PDO::PARAM_INT],
            [3, 3, PDO::PARAM_INT],
            [4, 4, PDO::PARAM_INT],
            [5, 5, PDO::PARAM_INT],
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::once())->method('prepare')->with(
            'SELECT u.id FROM users AS u WHERE (u.id IN(?,?,?,?,?))'
        )->willReturn($pdoStatementMock);

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());
        $stmt = $qb->createStatement($databaseConnection);
    }

    public function testInParameterString(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.username IN(:usernames)');
        $qb->setParameter('usernames', ["username", "two"]);

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects(self::exactly(2))->method('bindValue')->withConsecutive(
            [1, 'username', PDO::PARAM_STR],
            [2, 'two', PDO::PARAM_STR],
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::once())->method('prepare')->with(
            'SELECT u.id FROM users AS u WHERE (u.username IN(?,?))'
        )->willReturn($pdoStatementMock);

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());
        $qb->createStatement($databaseConnection);
    }

    public function testUnhandledTypeNull(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.username IN(:usernames)');
        $qb->setParameter('usernames', null);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::never())->method('prepare');

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unhandled type NULL');
        $qb->createStatement($databaseConnection);
    }

    public function testEmptyInParameter(): void
    {
        $qb = new QueryBuilder('users', 'u');
        $qb->addSelect('u.id');
        $qb->addWhereCondition('u.username IN(:usernames)');
        $qb->setParameter('usernames', []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects(self::never())->method('prepare');

        $databaseConnection = new DatabaseConnection($pdoMock, new MySQLDriver());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Array parameter is empty!');
        $stmt = $qb->createStatement($databaseConnection);
    }

    public function testParameterNotStartWithColon(): void
    {
        $qb = new QueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name should not start with colon');
        $qb->setParameter(':id', 1);
    }
}
