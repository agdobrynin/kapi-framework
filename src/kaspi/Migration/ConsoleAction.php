<?php

namespace Kaspi\Migration;

use Kaspi\Config;
use Kaspi\Db;
use splitbrain\phpcli\CLI;

class ConsoleAction
{
    private $config;
    private $db;
    private $pdoAdapter;
    private $tableName = 'migrations';
    private const SQLITE_ADAPTER = 'sqlite';

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->db = Db::getInstance($config);
        $this->pdoAdapter = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($table = $this->config->getMigrationTable()) {
            $this->tableName = $table;
        }
    }

    public function init(CLI $cli): void
    {
        // Проверить папку и создать куда скалдывать миграции
        if (($folder = $this->config->getMigrationPath()) && !is_dir($folder)) {
            if (!mkdir($folder, 0777, true)) {
                throw new MigrationException('Failed to create folder: ' . $folder);
            }
            $cli->info('Create migrations folder: ' . $folder);
        }

        // проверить и создать таблицу миграций
        $colTypeVersion = $this->pdoAdapter === self::SQLITE_ADAPTER ? 'INTEGER' : 'bigint(20)';
        $colTypeName = $this->pdoAdapter === self::SQLITE_ADAPTER ? 'TEXT' : 'varchar(100)';
        $colTypeStartTime = $this->pdoAdapter === self::SQLITE_ADAPTER ? 'TEXT' : 'timestamp';
        $colTypeEndTime = $this->pdoAdapter === self::SQLITE_ADAPTER ? 'TEXT' : 'timestamp';
        $colTypeBreakpoint = $this->pdoAdapter === self::SQLITE_ADAPTER ? 'INTEGER' : 'int(1)';
        $resultExec = $this->db->exec("CREATE TABLE IF NOT EXISTS {$this->tableName} (
                      version {$colTypeVersion} NOT NULL,
                      name {$colTypeName} NOT NULL,
                      start_time {$colTypeStartTime} NOT NULL,
                      end_time {$colTypeEndTime} NOT NULL,
                      breakpoint {$colTypeBreakpoint} DEFAULT 0)");
        if (false === $resultExec) {
            $err = implode(', ', $this->db->errorInfo());
            throw new MigrationException('Can not create migration table: '.$this->tableName. PHP_EOL . $err);
        }
        $cli->info('Create migrations table: ' . $this->tableName);
    }

    public function create(string $name): void
    {
        // Генерация класса миграции в папку созданную через init
    }

    public function migrate(?string $name): void
    {
        //выполнить миграцию, до включительно
    }

    public function rollback(?string $name): void
    {
        //откатить миграцию до, включительно
    }

    public function status(): ?array
    {
        $result = $this->db->query('SELECT * FROM '.$this->tableName. ' ORDER BY version')
            ->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
}