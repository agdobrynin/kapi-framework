<?php

namespace Kaspi\Orm\Query;

class Having
{
    // HAVING SUM(`YearlyIncome`) > 60000 AND SUM(`YearlyIncome`) < 200000
    /** @var array */
    protected $having = [];

    public function add(
        string $agrFunction,
        string $field,
        $value,
        ?string $compare = null,
        ?string $condition = null): self
    {
        if (!empty($field)) {
            $prefix = uniqid('', false).'_';
            $this->having[] = [
                'exp' => "{$agrFunction}({$field}) {$compare} :{$prefix}{$field}",
                'cond' => $condition ?: Condition::CONDITION_AND,
                'param' => ":{$prefix}{$field}",
                'value' => $value,
            ];
        }

        return $this;
    }

    public function unset(): void
    {
        $this->having = [];
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->having as $index => $havingItem) {
            if (0 === $index) {
                $result .= $havingItem['exp'];
            } else {
                $result .= ' '.$havingItem['cond'].' '.$havingItem['exp'];
            }
        }

        return 'HAVING '.$result;
    }

    public function makeStmData(): array
    {
        $result = [];
        foreach ($this->having as $index => $filter) {
            $result[$filter['param']] = $filter['value'];
        }

        return $result;
    }
}
