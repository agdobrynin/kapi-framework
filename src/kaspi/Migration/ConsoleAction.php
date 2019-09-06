<?php
declare(strict_types=1);

namespace Kaspi\Migration;

use Kaspi\{Config, Db};
use splitbrain\phpcli\{CLI, Colors};
use function \strtr, \file_put_contents;

class ConsoleAction
{
    private $config;
    private $db;
    private $tableName = 'migrations';
    private $cli;

    public function __construct(Config $config, CLI $cli)
    {
        $this->config = $config;
        $this->db = Db::getInstance($config);
        if ($table = $this->config->getMigrationTable()) {
            $this->tableName = $table;
        }
        $this->cli = $cli;
    }

    public function init(): void
    {
        // Проверить папку и создать куда скалдывать миграции
        if (($folder = $this->config->getMigrationPath()) && !is_dir($folder)) {
            if (!mkdir($folder, 0755, true)) {
                throw new MigrationException('Failed to create folder: ' . $folder);
            }
            $this->cli->info('Create migrations folder: ' . $folder);
        }

        // таблица миграций
        $sqlInit = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'migrations_init.sql');
        // Замeна переменных на значения
        $sqlInit = strtr($sqlInit, ['$tableName' => $this->tableName]);
        try {
            $this->db->beginTransaction();
            $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 1);
            $stmt = $this->db->prepare($sqlInit);
            $stmt->execute();
            $this->db->commit();
        } catch (PDOException $exception) {
            $this->db->rollBack();
            throw new MigrationException($exception->getMessage());
        }
        $this->cli->info('Create migrations table: ' . $this->tableName);
    }

    public function create(string $name): void
    {
        // Генерация класса миграции в папку созданную через init
        $migrationVersion = Utils::getCurrentTimestamp();
        $pathMigrations = $this->config->getMigrationPath();
        $className = Utils::migrationClassName($name);
        if (!$className) {
            throw new MigrationException(
                sprintf('%s - Use only alphabetical symbols for migration name', $name)
            );
        }
        // Проверка на существование миграции
        $arrMigration = Utils::migrationMap($pathMigrations);
        if (!empty($arrMigration[$className])) {
            throw new MigrationException(sprintf('Migration %s exist. Use other migration name', $className));
        }
        // TODO работать надо с бинарнобезопасным чтением
        $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'MigrationTemplate.php');
        // Замна переменных на значения
        $classes = [
            '$useNamespace' => __NAMESPACE__ . '\Migration',
            '$className' => Utils::snakeToCamel($name),
        ];
        $content = strtr($content, $classes);
        $fileName = Utils::migrationFileName($migrationVersion, $className);
        if (!$fileName) {
            throw new MigrationException(sprintf(
                    'Wrong parameners for migration file name : vesrion %s, class name %s',
                    $migrationVersion,
                    $className)
            );
        }
        $file = $pathMigrations . $fileName;
        // TODO работать надо с бинарнобезопасной записью
        if (file_put_contents($file, $content)) {
            $this->cli->info(sprintf('Create new migration %s and file %s: ', $className, $file));
        } else {
            $this->cli->error(sprintf('Can\'t careate migration file %s', $file));
        }
    }

    public function up(?int $migrationVersion): void
    {
        // выполнить миграцию, до включительно
        $start = microtime(true);
        $appliedMigrations = $this->takeAppliedMigrations();
        // чтобы не ехать дальше проверим вдруг уже миграция применена
        if ($migrationVersion && in_array($migrationVersion, array_values($appliedMigrations))) {
            throw new MigrationException(sprintf('Migration version %d already applied', $migrationVersion));
        }
        $pathMigrations = $this->config->getMigrationPath();
        $migrationsFileMap = Utils::migrationMap($pathMigrations);

        // расхождение массива файлов миграций и примененных миграций (лежат в бд)
        $migrationsForApplay = array_diff_assoc($migrationsFileMap, $appliedMigrations);
        if ($migrationVersion && !in_array($migrationVersion, array_values($migrationsForApplay))) {
            throw new MigrationException(sprintf('Ups! Migration version %d not found', $migrationVersion));
        }

        if (!$migrationVersion) {
            end($migrationsFileMap);
            $migrationVersion = $migrationsFileMap[key($migrationsFileMap)];
        }

        $this->cli->info(sprintf('Start migrations to version %s', $migrationVersion));
        $pathMigrations = $this->config->getMigrationPath();
        foreach ($migrationsForApplay as $className => $version) {
            $start_one = microtime(true);
            $migrationFile = Utils::migrationFileName($version, $className);
            require_once $pathMigrations . DIRECTORY_SEPARATOR . $migrationFile;
            /** @var Migration $migration */
            $migration = new $className($this->config);
            // Запускаем миграцию
            try {
                $this->db->beginTransaction();
                // запуск самой миграции
                $migration->up();
                $this->db->commit();
            } catch (PDOException $exception) {
                $this->db->rollBack();
                throw new MigrationException($exception->getMessage());
            }
            $this->cli->success(sprintf(
                'Migration "%s" applied from file "%s" Spended time %01.4f seconds',
                $className, $migrationFile, (microtime(true) - $start_one)
            ));
            // Записать созданную миграцию в таблицу
            try {
                $sqlInsert = 'INSERT INTO ' . $this->tableName . ' (version, name) VALUES (:version, :name)';
                $stmt = $this->db->prepare($sqlInsert);
                $stmt->execute([
                    'version' => $version,
                    'name' => $className
                ]);
            } catch (\PDOException $exception) {
                throw new MigrationException($exception->getMessage() . PHP_EOL . $sqlInsert);
            }
            if ($migrationVersion === (int)$version) {
                break;
            }
        }
        $timeSpended = microtime(true) - $start;
        $this->cli->success(sprintf('Migrations complete. Spended time %01.4f seconds', $timeSpended));
    }

    public function down(?int $migrationVersion): void
    {
        //откатить миграцию до, включительно
        $start = microtime(true);
        if (empty($migrationVersion)) {
            throw new MigrationException(sprintf('Set migration version for rollback'));
        }
        $appliedMigrations = $this->takeAppliedMigrations('DESC');
        if (false === in_array($migrationVersion, $appliedMigrations, true)) {
            throw new MigrationException(sprintf('Migration version %s not found', $migrationVersion));
        }

        $this->cli->info(sprintf('Start down migrations to version %s', $migrationVersion ?? 'initial stage'));
        $pathMigrations = $this->config->getMigrationPath();
        foreach ($appliedMigrations as $className => $version) {
            $start_one = microtime(true);
            $migrationFile = Utils::migrationFileName($version, $className);
            require_once $pathMigrations . DIRECTORY_SEPARATOR . $migrationFile;
            /** @var Migration $migration */
            $migration = new $className($this->config);
            // Запускаем миграцию
            try {
                $this->db->beginTransaction();
                // запуск самой миграции
                $migration->down();
                $this->db->commit();
            } catch (PDOException $exception) {
                $this->db->rollBack();
                throw new MigrationException($exception->getMessage());
            }
            // Удалить миграцию из таблицы
            try {
                $sqlDelete = 'DELETE FROM ' . $this->tableName . ' WHERE version = :version';
                $stmt = $this->db->prepare($sqlDelete);
                $stmt->execute(['version' => $version]);
            } catch (\PDOException $exception) {
                throw new MigrationException($exception->getMessage() . PHP_EOL . $sqlDelete);
            }
            $spendedTime = microtime(true) - $start_one;
            $this->cli->success(sprintf('Migration %s down from file %s. Spended time %01.4f seconds', $className, $migrationFile, $spendedTime));
            if ($migrationVersion === (int)$version) {
                break;
            }
        }
        $timeSpended = microtime(true) - $start;
        $this->cli->success(sprintf('Current migration version %s. Spended time %01.4f seconds', $migrationVersion, $timeSpended));
    }

    protected function takeAppliedMigrations(string $orderBy = 'ASC'): array
    {
        $result = [];
        $sql = 'SELECT version, name FROM ' . $this->tableName . ' ORDER BY version ' . $orderBy;
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[$row['name']] = (int)$row['version'];
        }
        return $result;
    }

    public function status(): ?array
    {
        $statusesFiles = [];
        $mapFiles = Utils::migrationMap($this->config->getMigrationPath());
        foreach ($mapFiles as $name => $version) {
            $statusesFiles[] = [
                'version' => $version,
                'name' => $name,
                'update_at' => '',
                'status' => '0',
            ];
        }

        $sql = 'SELECT version, name, update_at FROM ' . $this->tableName . ' ORDER BY version';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $statusesDb = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $statusesDb[] = [
                'version' => $row['version'],
                'name' => $row['name'],
                'update_at' => $row['update_at'],
                'status' => '1'
            ];
        }

        return $statusesDb + $statusesFiles;
    }
}
