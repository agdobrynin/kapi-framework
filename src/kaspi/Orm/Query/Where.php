<?php

namespace Kaspi\Orm\Query;

class Where
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

    protected $arrWhere = [];

    /**
     * @param string      $field     поле по которому строим условие
     * @param mixed       $value     значение условия
     * @param string|null $compare   оператор условия (>,<, IS NULL ...)
     * @param string|null $condition условие связывания этого стравнения к другим (AND, OR)
     *
     * @return Where
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
            $this->arrWhere[] = [
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
        $this->arrWhere = [];
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->arrWhere as $index => $where) {
            if (0 === $index) {
                $result .= $where['exp'];
            } else {
                $result .= ' '.$where['cond'].' '.$where['exp'];
            }
        }

        return ' WHERE '.$result;
    }

    public function makeStmData(): array
    {
        $result = [];
        foreach ($this->arrWhere as $index => $where) {
            $result[$where['param']] = $where['value'];
        }

        return $result;
    }
}
