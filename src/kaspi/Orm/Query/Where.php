<?php

namespace Kaspi\Orm\Query;

use Kaspi\Orm\OrmException;

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
    public const COMPARE_IN = 'IN';
    public const COMPARE_BETWEEN = 'BETWEEN';

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
            if (is_array($value)) {
                $plaseholders = [];
                foreach($value as $k => $val) {
                    $plaseholders[] = ":{$prefix}{$k}_{$field}";
                }
                if (self::COMPARE_IN === $compare) {
                    $exp = "{$field} {$compare} (" . implode(', ', $plaseholders) . ")";
                } elseif (2 === count($value) && self::COMPARE_BETWEEN === $compare) {
                    $exp = "{$field} {$compare} " . implode(' AND ', $plaseholders);
                } else {
                    throw new OrmException(sprintf('Undefined compare operator %s', $compare));
                }
            } else {
                $exp = "{$field} {$compare} :{$prefix}{$field}";
                $plaseholders = ":{$prefix}{$field}";
            }

            $this->arrWhere[] = [
                'exp' => $exp,
                'cond' => $condition ?: Condition::CONDITION_AND,
                'plaseholders' => $plaseholders,
                'value' => $value,
            ];
        }

        return $this;
    }

    public function addIn(string $field, array $values, ?string $condition = null): self
    {
        $this->add($field, $values, self::COMPARE_IN, $condition);
        return $this;
    }

    public function addInAnd(string $field, array $values): self
    {
        $this->add($field, $values, self::COMPARE_IN, Condition::CONDITION_AND);
        return $this;
    }

    public function addInOr(string $field, array $values): self
    {
        $this->add($field, $values, self::COMPARE_IN, Condition::CONDITION_OR);
        return $this;
    }

    public function addBetween(string $field, array $values, ?string $condition = null): self
    {
        $this->add($field, $values, self::COMPARE_BETWEEN, $condition);
        return $this;
    }

    public function addBetweenAnd(string $field, array $values): self
    {
        $this->add($field, $values, self::COMPARE_BETWEEN, Condition::CONDITION_AND);
        return $this;
    }

    public function addBetweenOr(string $field, array $values): self
    {
        $this->add($field, $values, self::COMPARE_BETWEEN, Condition::CONDITION_OR);
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
            if (is_array($where['plaseholders'])) {
                foreach ($where['plaseholders'] as $key => $plaseholder) {
                    $result[$plaseholder] = $where['value'][$key];
                }
            } else {
                $result[$where['plaseholders']] = $where['value'];
            }
        }

        return $result;
    }
}
