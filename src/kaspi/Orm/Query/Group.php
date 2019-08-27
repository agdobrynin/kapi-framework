<?php

namespace Kaspi\Orm\Query;

class Group
{
    /** @var array */
    protected $groupBy = [];

    public function add(string $field): self
    {
        if (!empty($field)) {
            $this->groupBy[] = $field;
        }

        return $this;
    }

    public function unset(): void
    {
        $this->groupBy = [];
    }

    public function __toString(): string
    {
        $result = '';
        if (!empty($this->groupBy)) {
            $result = 'GROUP BY '.implode(', ', $this->groupBy);
        }

        return $result;
    }
}
