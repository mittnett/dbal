<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

class AndX
{
    /**
     * @param list<string|AndX> $parts
     */
    public function __construct(
        public array $parts,
    ) {
        //
    }

    public function add(string|AndX $part): void
    {
        $this->parts[] = $part;
    }
}
