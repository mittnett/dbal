<?php
declare(strict_types=1);

namespace HbLib\DBAL\Driver;

interface DriverInterface
{
    public function quoteColumn(string $column): string;
}