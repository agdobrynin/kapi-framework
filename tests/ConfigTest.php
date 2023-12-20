<?php

namespace tests;

use Kaspi\Config;
use Kaspi\Exception\Core\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testEmptyConfig(): void
    {
        $config = new Config([]);
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('DSN for database is empty');
        $this->expectExceptionMessage('Path to templates for Kaspi\View is undefined');

        $this->assertFalse($config->displayErrorDetails());
        $this->assertNull($config->getDbUser());
        $this->assertNull($config->getDbPassword());
        $this->assertNull($config->getDbOptions());
        $this->assertNull($config->getMigrationPath());
        $this->assertNull($config->getMigrationTable());
        $this->assertFalse($config->getViewUseTemplateExtension());
        $this->assertNull($config->getViewConf());
        $this->assertNull($config->getCsrfTtl());
        $this->assertNull($config->getCsrfName());
        $this->assertNull($config->getCsrfLength());
        $this->assertFalse($config->getTrailingSlash());
        $this->assertEquals('UTC', $config->getTimeZone());
        $this->assertEquals(LC_ALL, $config->getLocaleCategory());
        $this->assertEquals([''], $config->getLocale());

        $config->getViewPath();
        $config->getDbDsnConfig();
    }

    public function testFullConfig(): void
    {
        $srcConfig = [
            'displayErrorDetails' => true,
            'db' => [
                'dsn' => 'sqlite:/var/www/store/db.db',
                'user' => 'root',
                'password' => 'password',
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
                'migration' => [
                    'path' => __DIR__,
                    'table' => 'migrations',
                ],
            ],
            'view' => [
                'path' => __DIR__,
                'useExtension' => 'php',
            ],
            'csrf' => [
                'ttl' => 1800,
                'name' => 'xCsrf',
                'length' => 32,
            ],
            'router' => [
                'trailingSlash' => true,
            ],
            'default_timezone' => 'Europe/Samara',
            'locale' => [
                'category' => LC_ALL,
                'locale' => ['ru_RU', 'RU_RU'],
            ],
        ];

        $config = new Config($srcConfig);

        $this->assertTrue($config->displayErrorDetails());
        $this->assertEquals('sqlite:/var/www/store/db.db', $config->getDbDsnConfig());
        $this->assertEquals('root', $config->getDbUser());
        $this->assertEquals('password', $config->getDbPassword());
        $this->assertEquals([\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION], $config->getDbOptions());
        $this->assertStringEndsWith('/tests/', $config->getMigrationPath());
        $this->assertEquals('migrations', $config->getMigrationTable());
        $this->assertStringEndsWith('tests', $config->getViewPath());
        $this->assertIsArray($config->getViewConf());
        $this->assertEquals('php', $config->getViewConf()['useExtension']);
        $this->assertTrue($config->getViewUseTemplateExtension());
        $this->assertEquals(1800, $config->getCsrfTtl());
        $this->assertEquals(32, $config->getCsrfLength());
        $this->assertEquals('xCsrf', $config->getCsrfName());
        $this->assertTrue($config->getTrailingSlash());
        $this->assertEquals('Europe/Samara', $config->getTimeZone());
        $this->assertEquals(['ru_RU', 'RU_RU'], $config->getLocale());
        $this->assertEquals(LC_ALL, $config->getLocaleCategory());
    }

    public function testRealPathForMigration(): void
    {
        $config = new Config(['db' => ['migration' => ['path' => '/aaa/aaa']]]);

        $this->assertNull($config->getMigrationPath());

        $config = new Config(['db' => ['migration' => ['path' => __DIR__]]]);
        $this->assertStringEndsWith('tests/', $config->getMigrationPath());
    }

    public function testRealPathForViewIsFailed(): void
    {
        $config = new Config(['view' => ['path' => '/aaa/aaa']]);
        $this->expectException(ConfigException::class);

        $config->getViewPath();
    }

    public function testRealPathForViewIsSuccess(): void
    {
        $config = new Config(['view' => ['path' => __DIR__]]);

        $this->assertStringEndsWith('tests', $config->getViewPath());
    }

    public function testSetters(): void
    {
        $config = new Config([]);

        $config->setDbDsnConfig('sqlite:/tmp/db.db');
        $this->assertEquals('sqlite:/tmp/db.db', $config->getDbDsnConfig());

        $config->setDbPassword('password');
        $this->assertEquals('password', $config->getDbPassword());

        $config->setDbUser('root');
        $this->assertEquals('root', $config->getDbUser());
    }
}
