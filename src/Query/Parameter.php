<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

#[\JetBrains\PhpStorm\Immutable]
class Parameter
{
    public function __construct(
        public string $name,
        public mixed $value,
    ) { }
}
