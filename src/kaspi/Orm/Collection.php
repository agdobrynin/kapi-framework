<?php

namespace Kaspi\Orm;

use Kaspi\Orm\Query\Filter;
use Kaspi\Orm\Query\Group;
use Kaspi\Orm\Query\Having;
use Kaspi\Orm\Query\Limit;
use Kaspi\Orm\Query\Order;

class Collection
{
    /** @var \PDOStatement */
    protected $pdoStatement;
    /** @var Entity */
    protected $entity;
    /** @var Filter */
    protected $filter;
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

    public function addFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function addLimit(Limit $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function addOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function addGroup(Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function addHaving(Having $having): self
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
            $this->filter,
            $this->order,
            $this->group,
            $this->having,
            $this->limit
        );
        return $this;
    }

    /**
     * Get result of collection
     * @return \Iterator
     */
    public function getEntities(): \Iterator
    {
        $this->pdoStatement->setFetchMode(\PDO::FETCH_CLASS, get_class($this->entity));
        while($record = $this->pdoStatement->fetch()) {
            yield $record;
        }
    }

    /**
     * Please use Collection::get for many result rows - it more effective, use Iterator through 'yield'
     * @return array
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
            $this->filter,
            $this->group
        );
    }
}
