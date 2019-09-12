<?php

namespace Kaspi\Orm;

use Kaspi\Orm\Query\EntityBuilder;
use Kaspi\Orm\Query\Limit;
use Kaspi\Orm\Query\Order;
use Kaspi\Orm\Query\Where;
use ReflectionClass;
use ReflectionProperty;

abstract class Entity
{
    /** @var ReflectionClass */
    private $entityClass;
    /** @var array */
    private $fields = [];
    /** @var EntityBuilder */
    private $entityBuilder;
    /** @var string */
    protected $table;
    /** @var string первичный ключ таблицы */
    protected $primaryKey = 'id';
    /** @var mixed свойство первичного ключа */
    public $id;

    protected function getEntityClass(): ReflectionClass
    {
        if (null === $this->entityClass) {
            try {
                $this->entityClass = new ReflectionClass($this);
            } catch (\ReflectionException $exception) {
                throw new OrmException($exception->getMessage());
            }
        }

        return $this->entityClass;
    }

    public function getProperties(): array
    {
        if (empty($this->fields)) {
            foreach ($this->getEntityClass()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $this->fields[] = $property->getName();
            }
        }

        return $this->fields;
    }

    private static function findOneByField(Entity $entity, ?string $fieldName = null, $value = null, $orderType = 'ASC'): Entity
    {
        $collection = (new Collection($entity));
        if (null !== $fieldName || null !== $value) {
            $fieldName = $fieldName ?: $entity->getPrimaryKey();
            $collection->where((new Where())->add($fieldName, $value));
        }
        $collection->order((new Order())->add($entity->getPrimaryKey(), $orderType));
        $collection->limit(new Limit(1, 1));

        return $collection->prepare()->getEntity();
    }

    /**
     * @param mixed $param Entity's primary key or array with pair fieldName=>value
     *
     * @throws OrmException
     */
    public static function find($param): Entity
    {
        $class = static::class;
        /** @var Entity $entity */
        $entity = new $class();
        if (is_array($param)) {
            [$fieldName, $value] = $param;
        } else {
            $fieldName = $entity->getPrimaryKey();
            $value = $param;
        }
        if (!in_array($fieldName, $entity->getProperties())) {
            throw new OrmException(sprintf('Entity do not have property %s', $fieldName));
        }

        return self::findOneByField($entity, $fieldName, $value ?: null);
    }

    public static function first(): Entity
    {
        $class = static::class;
        /** @var Entity $entity */
        $entity = new $class();

        return self::findOneByField($entity);
    }

    public static function last(): Entity
    {
        $class = static::class;
        /** @var Entity $entity */
        $entity = new $class();

        return self::findOneByField($entity, null, null, 'DESC');
    }

    /**
     * Удяляет всю таблицу Entity.
     */
    public static function truncate(): int
    {
        $class = static::class;
        /** @var Entity $entity */
        $entity = new $class();

        return $entity->getEntityBuilder()->truncate();
    }

    public function getEntityBuilder(): EntityBuilder
    {
        if (null === $this->entityBuilder) {
            $this->entityBuilder = new EntityBuilder($this);
        }

        return $this->entityBuilder;
    }

    public function getTable(): string
    {
        // имя таблицы во множественном числе + s если не опеделено в классе
        if (empty($this->table)) {
            $this->table = strtolower($this->getEntityClass()->getShortName()).'s';
        }

        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getEntityDataParams(bool $skipPrimaryKey = true): ?array
    {
        $paramsEntity = [];
        foreach ($this->getProperties() as $property) {
            if ($skipPrimaryKey && 'id' === $property) {
                continue;
            }
            $paramsEntity[$property] = $this->{$property};
        }

        return $paramsEntity;
    }

    public function save(): ?int
    {
        if ($this->id) {
            $this->getEntityBuilder()->update();
        } else {
            $this->getEntityBuilder()->insert();
        }

        return $this->id;
    }

    public function delete(): bool
    {
        if ($res = $this->getEntityBuilder()->delete()) {
            $this->empty();

            return $res;
        }

        return false;
    }

    protected function empty(): void
    {
        foreach ($this->getProperties() as $property) {
            $this->{$property} = null;
        }
    }
}
