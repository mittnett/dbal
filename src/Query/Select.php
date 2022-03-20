<?php
declare(strict_types=1);

namespace HbLib\DBAL\Query;

#[\JetBrains\PhpStorm\Immutable]
class Select
{
    public function __construct(
        public string $expr,
        public ?string $alias,
    ) { }
}
