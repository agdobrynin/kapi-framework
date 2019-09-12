<?php

namespace Kaspi\Orm;

use Kaspi\Orm\Query\Group;
use Kaspi\Orm\Query\Having;
use Kaspi\Orm\Query\Limit;
use Kaspi\Orm\Query\Order;
use Kaspi\Orm\Query\Where;

class Collection
{
    /** @var \PDOStatement */
    protected $pdoStatement;
    /** @var Entity */
    protected $entity;
    /** @var Where */
    protected $where;
    /** @var Order */
    protected $order;
    /** @var Group */
    protected $group;
    /** @var Having */
    protected $having;
    /** @var Limit */
    protected $limit;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function where(Where $where): self
    {
        $this->where = $where;

        return $this;
    }

    public function limit(Limit $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function order(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function group(Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function having(Having $having): self
    {
        $this->having = $having;

        return $this;
    }

    /**
     * @throws OrmException
     */
    public function prepare(): self
    {
        $this->pdoStatement = $this->entity->getEntityBuilder()->select(
            $this->where,
            $this->order,
            $this->group,
            $this->having,
            $this->limit
        );

        return $this;
    }

    /**
     * Get result of collection.
     */
    public function getEntities(): \Iterator
    {
        $this->pdoStatement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, get_class($this->entity));
        while ($record = $this->pdoStatement->fetch()) {
            yield $record;
        }
    }

    /**
     * Please use Collection::get for many result rows - it more effective, use Iterator through 'yield'.
     */
    public function getArray(): array
    {
        return $this->entity->getEntityBuilder()->fetchAll($this->pdoStatement);
    }

    public function getEntity(): Entity
    {
        return $this->entity->getEntityBuilder()->fetch($this->pdoStatement);
    }

    /**
     * @throws OrmException
     */
    public function count(): int
    {
        return $this->entity->getEntityBuilder()->count(
            $this->where,
            $this->group
        );
    }
}
