<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

class Join
{
    /**
     * Join constructor.
     * @param string $expr
     * @param array<int, string|AndX> $conditions
     */
    public function __construct(
        private string $expr,
        private array $conditions,
    ) {
        //
    }

    /**
     * @return array<int, string|AndX>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getExpr(): string
    {
        return $this->expr;
    }
}
