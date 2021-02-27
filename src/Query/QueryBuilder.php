<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

use HbLib\DBAL\DatabaseConnectionInterface;
use LogicException;
use PDOStatement;
use function count;
use function implode;

final class QueryBuilder
{
    /**
     * @param string[] $selects
     * @param Join[] $joinConditions
     * @param string $from
     * @param array<int, string|AndX> $whereConditions
     * @param Parameter[] $parameters
     * @param string[] $orderBy
     * @param string[] $groupBy
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(
        private array $selects = [],
        private string $from = '',
        private array $joinConditions = [],
        private array $whereConditions = [],
        private array $parameters = [],
        private array $orderBy = [],
        private array $groupBy = [],
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
        //
    }

    public function addSelect(string $select): void
    {
        $this->selects[] = $select;
    }

    public function setFrom(string $expr): void
    {
        $this->from = $expr;
    }

    /**
     * @param string $join
     * @param array<int, string|AndX> $conditions
     */
    public function addJoinCondition(string $join, array $conditions): void
    {
        $this->joinConditions[] = new Join($join, $conditions);
    }

    public function addWhereCondition(string|AndX $condition): void
    {
        $this->whereConditions[] = $condition;
    }

    public function addOrderBy(string $expr): void
    {
        $this->orderBy[] = $expr;
    }

    public function addGroupBy(string $field): void
    {
        $this->groupBy[] = $field;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setParameter(string $name, mixed $value): void
    {
        if (str_starts_with($name, ':') === true) {
            throw new LogicException('Name should not start with colon');
        }

        $this->parameters[] = new Parameter($name, $value);
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function setOffset(?int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * Translates the query builder into a proper SQL query with parameter names left intact.
     * @return string
     */
    public function getSQL(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->selects) . ' FROM ' . $this->from;

        foreach ($this->joinConditions as $join) {
            $sql .= " {$join->getExpr()}";

            $joinConditions = $join->getConditions();
            if (count($joinConditions) > 0) {
                $sql .= ' ON ' . $this->generateCondition(new AndX($joinConditions));
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
            $sql .= ' LIMIT ' . ($this->offset !== null ? $this->offset . ', ' : '') . '' . $this->limit;
        }

        return $sql;
    }

    /**
     * Create a prepared statement with parameters bound which can later be executed.
     *
     * @param DatabaseConnectionInterface $connection
     * @return PDOStatement
     */
    public function createStatement(DatabaseConnectionInterface $connection): PDOStatement
    {
        $sql = $this->getSQL();

        $sqlParameters = [];
        $sqlParameterTypes = [];
        $sqlParameterReplacements = [];

        if (preg_match_all('/:[a-z0-9_-]+/ui', $sql, $matches) > 0) {
            $parametersByKey = [];
            foreach ($this->parameters as $parameter) {
                $parametersByKey[':' . $parameter->getName()] = $parameter;
            }

            foreach ($matches[0] as $parameterName) {
                $parameterValue = $parametersByKey[$parameterName]->getValue();

                if (is_array($parameterValue) === true) {
                    if (count($parameterValue) === 0) {
                        throw new LogicException('Array parameter is empty!');
                    }

                    $sqlParameterReplacements[$parameterName] = '?' . str_repeat(',?', count($parameterValue) - 1);

                    foreach ($parameterValue as $value) {
                        $sqlParameters[] = $value;
                        $sqlParameterTypes[] = is_int($value) === true ? \PDO::PARAM_INT : \PDO::PARAM_STR;
                    }

                    continue;
                }

                $sqlParameterReplacements[$parameterName] = '?';

                if (is_bool($parameterValue) === true) {
                    $sqlParameters[] = $parameterValue === true ? 1 : 0;
                    $sqlParameterTypes[] = \PDO::PARAM_INT;
                    continue;
                }

                if (is_string($parameterValue) === true || is_float($parameterValue) === true) {
                    $sqlParameters[] = (string) $parameterValue;
                    $sqlParameterTypes[] = \PDO::PARAM_STR;
                    continue;
                }

                if (is_int($parameterValue) === true) {
                    $sqlParameters[] = $parameterValue;
                    $sqlParameterTypes[] = \PDO::PARAM_INT;
                    continue;
                }

                throw new LogicException('Unhandled type ' . gettype($parameterValue));
            }
        }

        $sql = strtr($sql, $sqlParameterReplacements);
        unset($sqlParameterReplacements);

        $stmt = $connection->prepare($sql);

        foreach ($sqlParameters as $i => $value) {
            $stmt->bindValue($i + 1, $value, $sqlParameterTypes[$i]);
        }

        return $stmt;
    }

    private function generateCondition(string|AndX $condition): string
    {
        if ($condition instanceof AndX) {
            // and or or grouping.

            $parts = [];
            foreach ($condition->getParts() as $part) {
                $parts[] = $this->generateCondition($part);
            }

            return '(' . implode($condition instanceof OrX ? ' OR ' : ' AND ', $parts) . ')';
        }

        return $condition;
    }
}
