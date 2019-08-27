<?php

namespace Kaspi\Orm\Query;

class Filter
{
    public const COMPARE_EQUAL = '=';
    public const COMPARE_LIKE = 'LIKE';
    public const COMPARE_LESS = '<';
    public const COMPARE_LESS_OR_EQUAL = '<=';
    public const COMPARE_MORE = '>';
    public const COMPARE_MORE_OR_EQUAL = '>=';
    // при формировании sql убирать value и из массива значений  stm
    public const COMPARE_IS_NULL = 'IS NULL';
    // при формировании sql убирать value и из массива значений  stm
    public const COMPARE_IS_NOT_NULL = 'IS NOT NULL';

    protected $arrFilters = [];

    /**
     * @param string      $field     поле по которому строим условие
     * @param mixed       $value     значение условия
     * @param string|null $compare   оператор условия (>,<, IS NULL ...)
     * @param string|null $condition условие связывания этого стравнения к другим (AND, OR)
     *
     * @return Filter
     */
    public function add(
        string $field,
        $value,
        ?string $compare = null,
        ?string $condition = null
    ): self {
        if (!empty($field)) {
            $compare = $compare ?: self::COMPARE_EQUAL;
            $prefix = uniqid('', false).'_';
            $this->arrFilters[] = [
                'exp' => "{$field} {$compare} :{$prefix}{$field}",
                'cond' => $condition ?: Condition::CONDITION_AND,
                'param' => ":{$prefix}{$field}",
                'value' => $value,
            ];
        }

        return $this;
    }

    public function addEqualAnd(string $field, $value): self
    {
        return $this->add($field, $value, self::COMPARE_EQUAL, Condition::CONDITION_AND);
    }

    public function addEqualOr(string $field, $value): self
    {
        return $this->add($field, $value, self::COMPARE_EQUAL, Condition::CONDITION_OR);
    }

    public function addLikeAnd(string $field, $value): self
    {
        return $this->add($field, $value, self::COMPARE_LIKE, Condition::CONDITION_AND);
    }

    public function addLikeOr(string $field, $value): self
    {
        return $this->add($field, $value, self::COMPARE_LIKE, Condition::CONDITION_OR);
    }

    public function unset(): void
    {
        $this->arrFilters = [];
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->arrFilters as $index => $filter) {
            if (0 === $index) {
                $result .= $filter['exp'];
            } else {
                $result .= ' '.$filter['cond'].' '.$filter['exp'];
            }
        }

        return ' WHERE '.$result;
    }

    public function makeStmData(): array
    {
        $result = [];
        foreach ($this->arrFilters as $index => $filter) {
            $result[$filter['param']] = $filter['value'];
        }

        return $result;
    }
}
