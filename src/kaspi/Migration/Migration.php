<?php
declare(strict_types=1);

namespace kaspi\Migration;

use Kaspi\Config;
use Kaspi\Db;
use Kaspi\Migration\MigrationInterface;

class Migration implements MigrationInterface
{
    /** @var Config  */
    private $config;
    /** @var \PDO Работа с SQL как со стандартным PDO драйвером! */
    protected $db;
    /** @var string текущий драйвер PDO (mysql, sqlite, ...) */
    protected $pdoDriver;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->db = Db::getInstance($config);
        $this->pdoDriver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * скрипт внесения миграции
     */
    public function up(): void
    {
        // TODO: Implement up() method.
    }

    /**
     * скрипт удаления миграции
     */
    public function down(): void
    {
        // TODO: Implement down() method.
    }
}