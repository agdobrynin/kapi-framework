<?php

namespace Kaspi\Orm\Query;

use Kaspi\Db;
use Kaspi\Orm\Entity;
use Kaspi\Orm\OrmException;

final class EntityBuilder
{
    private $entity;
    /** @var \PDO */
    private static $pdo;

    /** @var bool использовать транзации при операциях PDOStatement::execute */
    public $useTransaction = true;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public static function getPdo(): \PDO
    {
        if (null === self::$pdo) {
            self::$pdo = Db::getInstance();
        }

        return self::$pdo;
    }

    /**
     * @throws OrmException
     */
    public function update(): bool
    {
        $this->checkPrimaryKey();
        $paramsData = new ParamsData($this->entity->getEntityDataParams());

        $format = 'UPDATE %s SET %s WHERE %s = :%s';
        $sql = sprintf(
            $format,
            $this->entity->getTable(),
            $paramsData->getPairs(),
            $this->entity->getPrimaryKey(),
            $this->entity->getPrimaryKey()
        );

        $stmData = $paramsData->getStmData();
        $stmData[':'.$this->entity->getPrimaryKey()] = $this->entity->id;
        $this->execute($sql, $stmData, $execResult);
        if ($this->useTransaction) {
            self::getPdo()->commit();
        }

        return $execResult;
    }

    /**
     * @throws OrmException
     */
    public function insert(): bool
    {
        $paramsData = new ParamsData($this->entity->getEntityDataParams());
        $format = 'INSERT INTO %s (%s) VALUES(%s)';
        $sql = sprintf($format, $this->entity->getTable(), $paramsData->getFields(), $paramsData->getValues());

        $this->execute($sql, $paramsData->getStmData(), $execResult);
        $this->entity->id = self::getPdo()->lastInsertId($this->entity->getPrimaryKey());
        if ($this->useTransaction) {
            self::getPdo()->commit();
        }

        return $execResult;
    }

    /**
     * @throws OrmException
     */
    public function delete(): bool
    {
        $this->checkPrimaryKey();
        $format = 'DELETE FROM %s WHERE %s = :%s LIMIT 1';
        $sql = sprintf($format, $this->entity->getTable(), $this->entity->getPrimaryKey(), $this->entity->getPrimaryKey());

        $stmData[':'.$this->entity->getPrimaryKey()] = $this->entity->id;
        $this->execute($sql, $stmData, $execResult);
        if ($this->useTransaction) {
            self::getPdo()->commit();
        }

        return $execResult;
    }

    /**
     * @throws OrmException
     */
    public function truncate(): int
    {
        // TODO подумать о разных диалектах очистки таблицы, например для mysql
        $sql = 'DELETE FROM '.$this->entity->getTable();
        $sth = $this->execute($sql, [], $execResult);
        if ($this->useTransaction) {
            self::getPdo()->commit();
        }

        return $sth->rowCount();
    }

    /**
     * @throws OrmException
     */
    public function select(
        ?Where $where = null,
        ?Order $order = null,
        ?Group $group = null,
        ?Having $having = null,
        ?Limit $limit = null
    ): \PDOStatement {
        $paramsData = new ParamsData($this->entity->getEntityDataParams());
        $format = 'SELECT %s, %s FROM %s';
        $sql = sprintf($format, $this->entity->getPrimaryKey(), $paramsData->getFields(), $this->entity->getTable());
        $stmData = [];
        if ($where && $strWhere = (string) $where) {
            $sql .= ' '.$strWhere;
            $stmData += $where->makeStmData();
        }
        if ($group && $strGroup = (string) $group) {
            $sql .= ' '.$strGroup;
        }
        if ($having && $strHaving = (string) $having) {
            $sql .= ' '.$strHaving;
            $stmData += $having->makeStmData();
        }
        if ($order && $strOrder = (string) $order) {
            $sql .= ' '.$strOrder;
        }
        if ($limit && $strLimit = (string) $limit) {
            $sql .= ' '.$strLimit;
        }

        return $this->execute($sql, $stmData);
    }

    public function fetchAll(\PDOStatement $sth): array
    {
        return $sth->fetchAll(\PDO::FETCH_CLASS, get_class($this->entity)) ?: [];
    }

    public function fetch(\PDOStatement $sth): Entity
    {
        $sth->setFetchMode(\PDO::FETCH_CLASS, get_class($this->entity));

        return $sth->fetch() ?: $this->entity;
    }

    /**
     * @throws OrmException
     */
    public function count(?Where $where = null, ?Group $group = null): int
    {
        $format = 'SELECT COUNT(%s) FROM %s';
        $sql = sprintf($format, $this->entity->getPrimaryKey(), $this->entity->getTable());
        if ($where && $strWhere = (string) $where) {
            $sql .= ' '.$strWhere;
        }
        if ($group && $strGroup = (string) $group) {
            $sql .= ' '.$strGroup;
        }
        $sql .= ' LIMIT 1';

        $stmData = $where ? $where->makeStmData() : [];
        $sth = $this->execute($sql, $stmData);
        $result = $sth->fetch(\PDO::FETCH_NUM)[0] ?? 0;

        return $result;
    }

    /**
     * @throws OrmException
     */
    private function execute(string $sql, array $inputParameters, ?bool &$execResult = null): \PDOStatement
    {
        // TODO придумай как обрабатывать ошибки
        $isSelect = 0 === stripos($sql, 'select ');
        try {
            if (false === $isSelect && $this->useTransaction) {
                self::getPdo()->beginTransaction();
            }
            $sth = self::getPdo()->prepare($sql);
            $execResult = $sth->execute($inputParameters);

            return $sth;
        } catch (\PDOException $exception) {
            if (false === $isSelect && $this->useTransaction) {
                self::getPdo()->rollBack();
            }
            throw new OrmException($exception->getMessage().PHP_EOL.$sql);
        }
    }

    /**
     * @throws OrmException
     */
    private function checkPrimaryKey(): void
    {
        if (empty($this->entity->id)) {
            throw new OrmException(
                sprintf(
                    'Entity for table %s have empty primary key %s',
                    $this->entity->getTable(),
                    $this->entity->getPrimaryKey()
                )
            );
        }
    }
}
