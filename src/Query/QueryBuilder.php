<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

use HbLib\DBAL\DatabaseConnectionInterface;
use InvalidArgumentException;
use PDOStatement;
use RuntimeException;
use PDO;
use function count;
use function implode;

/**
 * @phpstan-type JoinMode 'INNER'|'LEFT'|'RIGHT'
 */
final class QueryBuilder
{
    /** @var Select[] */
    private array $selects = [];
    /** @var Join[] */
    private array $joinConditions = [];
    /** @var list<string|AndX> */
    private array $whereConditions = [];
    /** @var Parameter[] */
    private array $parameters = [];
    /** @var string[] */
    private array $orderBy = [];
    /** @var string[] */
    private array $groupBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(
        private string $from = '',
        private ?string $fromAlias = null
    ) { }

    public function addSelect(string $expr, ?string $alias = null): self
    {
        $this->selects[] = new Select($expr, $alias);

        return $this;
    }

    public function setFrom(string $expr, ?string $alias = null): self
    {
        $this->from = $expr;
        $this->fromAlias = $alias;

        return $this;
    }

    /**
     * @phpstan-param JoinMode $join
     * @param list<string|AndX> $conditions
     */
    public function addJoinCondition(string $join, string $table, ?string $alias = null, array $conditions = []): self
    {
        $this->joinConditions[] = new Join($join, $table, $alias, $conditions);

        return $this;
    }

    /**
     * @param string|AndX $condition
     * @return self
     */
    public function addWhereCondition(string|AndX $condition): self
    {
        $this->whereConditions[] = $condition;

        return $this;
    }

    /**
     * @param string $expr
     * @return self
     */
    public function addOrderBy(string $expr): self
    {
        $this->orderBy[] = $expr;

        return $this;
    }

    public function addGroupBy(string $field): self
    {
        $this->groupBy[] = $field;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParameter(string $name, mixed $value): self
    {
        if (str_starts_with($name, ':') === true) {
            throw new InvalidArgumentException('Name should not start with colon');
        }

        $this->parameters[] = new Parameter($name, $value);

        return $this;
    }

    /**
     * @param int|null $limit
     * @return self
     */
    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int|null $offset
     * @return self
     */
    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Translates the query builder into a proper SQL query with parameter names left intact.
     * @return string
     */
    public function getSQL(): string
    {
        $sql = 'SELECT ' . implode(', ', array_map(
            fn (Select $select): string => $this->getExprWithAlias($select->expr, $select->alias),
            $this->selects,
        )) . ' FROM ' . $this->getExprWithAlias($this->from, $this->fromAlias);

        foreach ($this->joinConditions as $join) {
            $sql .= ' ' . $join->join . ' JOIN ' . $this->getExprWithAlias($join->table, $join->alias);

            if (count($join->conditions) > 0) {
                $sql .= ' ON ' . $this->generateCondition(new AndX($join->conditions));
            }
        }

        if (count($this->whereConditions) > 0) {
            $sql .= ' WHERE ' . $this->generateCondition(new AndX($this->whereConditions));
        }

        if (count($this->groupBy) > 0) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (count($this->orderBy) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . ($this->offset !== null ? $this->offset . ', ' : '') . $this->limit;
        }

        return $sql;
    }

    private function getExprWithAlias(string $mainExpr, ?string $alias): string
    {
        return $mainExpr . ($alias !== null ? ' AS ' . $alias : '');
    }

    /**
     * Generates the appropriate SQL for execution in index 0, the parameters in a list in index 1, and
     * the PDO::PARAM_* constants in index 2.
     *
     * @phpstan-return array{string, list<mixed>, list<int>}
     */
    public function generateSQLAndParameters(): array
    {
        $sql = $this->getSQL();

        $sqlParameters = [];
        $sqlParameterTypes = [];
        $sqlParameterReplacements = [];

        if (preg_match_all('/:[a-z0-9_-]+/ui', $sql, $matches) > 0) {
            $parametersByKey = [];
            foreach ($this->parameters as $parameter) {
                $parametersByKey[':' . $parameter->name] = $parameter;
            }

            foreach ($matches[0] as $parameterName) {
                $parameterValue = $parametersByKey[$parameterName]->value;

                if (is_array($parameterValue) === true) {
                    if (count($parameterValue) === 0) {
                        throw new RuntimeException('Array parameter is empty!');
                    }

                    $sqlParameterReplacements[$parameterName] = '?' . str_repeat(',?', count($parameterValue) - 1);

                    foreach ($parameterValue as $value) {
                        $sqlParameters[] = $value;
                        $sqlParameterTypes[] = is_int($value) === true ? PDO::PARAM_INT : PDO::PARAM_STR;
                    }

                    continue;
                }

                $sqlParameterReplacements[$parameterName] = '?';

                if (is_bool($parameterValue) === true) {
                    $sqlParameters[] = $parameterValue === true ? 1 : 0;
                    $sqlParameterTypes[] = PDO::PARAM_INT;
                    continue;
                }

                if (is_string($parameterValue) === true || is_float($parameterValue) === true) {
                    $sqlParameters[] = (string) $parameterValue;
                    $sqlParameterTypes[] = PDO::PARAM_STR;
                    continue;
                }

                if (is_int($parameterValue) === true) {
                    $sqlParameters[] = $parameterValue;
                    $sqlParameterTypes[] = PDO::PARAM_INT;
                    continue;
                }

                throw new RuntimeException('Unhandled type ' . gettype($parameterValue));
            }
        }

        return [
            strtr($sql, $sqlParameterReplacements),
            $sqlParameters,
            $sqlParameterTypes,
        ];
    }

    /**
     * Create a prepared statement with parameters bound which can later be executed.
     *
     * @param DatabaseConnectionInterface $connection
     * @return PDOStatement
     */
    public function createStatement(DatabaseConnectionInterface $connection): PDOStatement
    {
        [$sql, $sqlParameters, $sqlParameterTypes] = $this->generateSQLAndParameters();

        $stmt = $connection->prepare($sql);

        foreach ($sqlParameters as $i => $value) {
            $stmt->bindValue($i + 1, $value, $sqlParameterTypes[$i]);
        }

        return $stmt;
    }

    private function generateCondition(string|AndX $condition): string
    {
        if ($condition instanceof AndX) {
            // 'and' or 'or' grouping.

            $parts = [];
            foreach ($condition->parts as $part) {
                $parts[] = $this->generateCondition($part);
            }

            return '(' . implode($condition instanceof OrX ? ' OR ' : ' AND ', $parts) . ')';
        }

        return $condition;
    }
}
