<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

class AndX
{
    /**
     * @param array<int, string|AndX> $parts
     */
    public function __construct(
        private array $parts,
    ) {
        //
    }

    public function add(string|AndX $part): void
    {
        $this->parts[] = $part;
    }

    /**
     * @return array<int, string|AndX>
     */
    public function getParts(): array
    {
        return $this->parts;
    }
}
