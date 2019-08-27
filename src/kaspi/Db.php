<?php

namespace Kaspi;

class Db
{
    private static $connection;

    public function __construct(Config $config)
    {
        self::$connection = new \PDO(
            $config->getDbDsnConfig(),
            $config->getDbUser(),
            $config->getDbPassword(),
            $config->getDbOptions()
        );
        self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance(?Config $config = null): \PDO
    {
        if (null === self::$connection) {
            new static($config);
        }

        return self::$connection;
    }
}
