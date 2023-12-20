<?php

namespace Kaspi;

use Kaspi\Exception\Core\ConfigException;

class Config
{
    private $config;

    public function __construct(array $arrConfig)
    {
        $this->config = $arrConfig;
    }

    public function displayErrorDetails(): bool
    {
        return $this->config['displayErrorDetails'] ?? false;
    }

    public function getDbDsnConfig(): string
    {
        if (empty($this->config['db']['dsn'])) {
            throw new ConfigException('DSN for database is empty');
        }

        return $this->config['db']['dsn'];
    }

    public function setDbDsnConfig(string $dsn): void
    {
        $this->config['db']['dsn'] = $dsn;
    }

    public function getDbUser(): ?string
    {
        return $this->config['db']['user'] ?: null;
    }

    public function setDbUser(string $user): void
    {
        $this->config['db']['user'] = $user;
    }

    public function getDbPassword(): ?string
    {
        return $this->config['db']['password'] ?: null;
    }

    public function setDbPassword(string $password): ?string
    {
        return $this->config['db']['password'] = $password;
    }

    public function getDbOptions(): ?array
    {
        return $this->config['db']['options'] ?: null;
    }

    public function getMigrationPath(): ?string
    {
        if (empty($this->config['db']['migration']['path'])) {
            return null;
        }

        $realPath = realpath($this->config['db']['migration']['path']);

        return $realPath ? $realPath.DIRECTORY_SEPARATOR : null;
    }

    public function getMigrationTable(): ?string
    {
        return $this->config['db']['migration']['table'] ?? null;
    }

    public function getViewPath(): ?string
    {
        $path = $this->config['view']['path'] ?: null;

        if (empty($path)) {
            throw new ConfigException('Path to templates for Kaspi\View is undefined');
        }

        $realPath = realpath($path);

        if (is_dir($realPath)) {
            return $realPath;
        }

        throw new ConfigException(sprintf('Directory `%s` for templates not found', $path));
    }

    public function getViewUseTemplateExtension(): bool
    {
        return $this->config['view']['useExtension'] ?: false;
    }

    public function getViewConf(): ?array
    {
        return $this->config['view'] ?? null;
    }

    public function getCsrfTtl(): ?int
    {
        return $this->config['csrf']['ttl'] ?? null;
    }

    public function getCsrfName(): ?string
    {
        return $this->config['csrf']['name'] ?? null;
    }

    public function getCsrfLength(): ?int
    {
        return $this->config['csrf']['length'] ?? null;
    }

    public function getTrailingSlash(): bool
    {
        return $this->config['router']['trailingSlash'] ?? false;
    }

    public function getTimeZone(): string
    {
        return $this->config['default_timezone'] ?? 'UTC';
    }

    public function getLocaleCategory(): int
    {
        return $this->config['locale']['category'] ?? LC_ALL;
    }

    public function getLocale(): array
    {
        $locale = $this->config['locale']['locale'] ?? '';
        if (is_string($locale)) {
            return [$locale];
        }
        if (is_array($locale)) {
            return $locale;
        }
        throw new ConfigException('Config for set locale is wrong');
    }
}
