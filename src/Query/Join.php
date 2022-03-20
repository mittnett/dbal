<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

/**
 * @phpstan-import-type JoinMode from \HbLib\DBAL\Query\QueryBuilder
 */
#[\JetBrains\PhpStorm\Immutable]
class Join
{
    /**
     * @phpstan-param JoinMode $join
     * @param list<string|AndX> $conditions
     */
    public function __construct(
        public string $join,
        public string $table,
        public ?string $alias,
        public array $conditions,
    ) { }
}
