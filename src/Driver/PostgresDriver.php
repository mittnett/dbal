<?php
declare(strict_types=1);

namespace HbLib\DBAL\Driver;

class PostgresDriver implements DriverInterface
{
    public function quoteColumn(string $column): string
    {
        return '"' . addslashes($column) . '"';
    }
}