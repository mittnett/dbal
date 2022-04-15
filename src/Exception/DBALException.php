<?php
declare(strict_types=1);

namespace HbLib\DBAL\Exception;

use Exception;

class DBALException extends Exception
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct(message: $message, previous: $previous);
    }
}
