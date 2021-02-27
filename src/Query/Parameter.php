<?php declare(strict_types=1);

namespace HbLib\DBAL\Query;

class Parameter
{
    /**
     * Parameter constructor.
     * @param string $name
     * @param mixed $value
     */
    public function __construct(
        private string $name,
        private mixed $value,
    ) {
        //
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
