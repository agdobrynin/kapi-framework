<?php
declare(strict_types=1);

namespace kaspi\Migration;

class Utils
{
    public const DATE_FORMAT = 'YmdHis';
    public const MIGRATION_FILE_NAME_PATTERN = '/^(?<version>[0-9]{14})_(?<name>[_a-z]+).php$/i';
    public const MIGRATION_FILE_NAME = '%d_%s.php';
    public const CLASS_NAME_PATTERN = '/^([a-z]+)$/i';

    public static function getCurrentTimestamp(): int
    {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));

        return (int)$dt->format(static::DATE_FORMAT);
    }

    public static function camelToSnake(string $input): ?string
    {
        $r = strtolower(preg_replace_callback('/([a-z])([A-Z])/', function ($a) {
            return $a[1] . "_" . strtolower($a[2]);
        }, $input));
        return $r;
    }

    public static function snakeToCamel(string $input): string
    {
        return str_replace('_', '', ucwords($input, '_'));
    }

    public static function migrationMap(string $path): ?array
    {
        $path = realpath($path);
        if (!is_dir($path)) {
            throw new MigrationException(sprintf('Path %s to migrations directory not found. Use init command', $path));
        }
        $classNames = [];
        $phpFiles = glob($path . DIRECTORY_SEPARATOR . '*.php');
        foreach ($phpFiles as $filePath) {
            if (1 === preg_match(self::MIGRATION_FILE_NAME_PATTERN, basename($filePath), $matches)) {
                $classNames[self::snakeToCamel($matches['name'])] = (int)$matches['version'];
            }
        }
        asort($classNames);
        return $classNames;
    }

    public static function migrationFileName(int $version, string $className): ?string
    {
        $fileClassName = sprintf(self::MIGRATION_FILE_NAME, $version, self::camelToSnake($className));
        if (1 === preg_match(self::MIGRATION_FILE_NAME_PATTERN, $fileClassName, $matches)) {
            return $fileClassName;
        }
        return null;
    }

    public static function migrationClassName(string $name): ?string
    {
        if (1 === preg_match(self::CLASS_NAME_PATTERN, $name)) {
            return self::snakeToCamel($name);
        }
        return null;
    }
}
