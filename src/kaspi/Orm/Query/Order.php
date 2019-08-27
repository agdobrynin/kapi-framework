<?php

namespace Kaspi\Orm\Query;

class Order
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    /** @var array */
    protected $orderBy = [];

    public function add(string $field, string $sortOrder): self
    {
        if (!empty($field) && !empty($sortOrder) && in_array(strtoupper($sortOrder), [self::ASC, self::DESC], true)) {
            $this->orderBy[] = sprintf('`%s` %s', $field, $sortOrder);
        }

        return $this;
    }

    public function unset(): void
    {
        $this->orderBy = [];
    }

    public function __toString(): string
    {
        $result = '';
        if (!empty($this->orderBy)) {
            $result = 'ORDER BY '.implode(', ', $this->orderBy);
        }

        return $result;
    }
}
