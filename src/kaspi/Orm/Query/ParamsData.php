<?php

namespace Kaspi\Orm\Query;

class ParamsData
{
    protected $sourceData;
    protected $fields;
    protected $values;
    protected $insert;
    protected $stmData;

    public function __construct(array $data)
    {
        $this->sourceData = $data;
    }

    /**
     * Возвращает подготовленный массив для использования в PDOStatement::execute в виде
     * [
     *      ':field1' => valueOfField1,
     *      ':field2' => valueOfField2,
     * ].
     */
    public function getStmData(): array
    {
        if (empty($this->stmData)) {
            $this->getParamsData();
        }

        return $this->stmData;
    }

    /**
     * возвращает подготовленную строку с именами полей вида field1, field2,...
     * применяется для inset заспросов.
     */
    public function getFields(): string
    {
        if (empty($this->fields)) {
            $this->getParamsData();
        }

        return $this->fields;
    }

    /**
     * возвращает подготовленную строку с переменными для PDO::execute как названия полей
     * в виде :field1, :field2, ...
     * применяется для update запросов.
     */
    public function getValues(): string
    {
        if (empty($this->values)) {
            $this->getParamsData();
        }

        return $this->values;
    }

    /**
     * возвращает подготовленную строку пар поле = :переменная
     * в виде `userName` = :userName, `email` = :email, ...
     */
    public function getPairs(): string
    {
        if (empty($this->insert)) {
            $this->getParamsData();
        }

        return $this->insert;
    }

    protected function getParamsData(): void
    {
        $this->fields = implode(', ', array_keys($this->sourceData));
        $keys = array_keys($this->sourceData);
        $template = array_map(
            function ($key) {
                return ":{$key}";
            },
            $keys
        );
        $templateInsert = array_map(
            function ($key) {
                return "`{$key}` = :{$key}";
            },
            $keys
        );
        $this->insert = implode(', ', $templateInsert);
        $this->stmData = array_combine($template, $this->sourceData);
        foreach ($this->stmData as $key => $value) {
            if (null === $value) {
                $this->stmData[$key] = '';
            }
        }
        $this->values = implode(', ', $template);
    }
}
