<?php

namespace Kaspi;

use Kaspi\Exception\ConfigException;

class Config
{
    private $config;

    public function __construct(array $arrConfig)
    {
        $this->config = $arrConfig;
    }

    public function getDbDsnConfig(): string
    {
        if (empty($this->config['db']['dsn'])) {
            throw new ConfigException('DSN for database is empty');
        }

        return $this->config['db']['dsn'];
    }

    public function getDbUser(): ?string
    {
        return $this->config['db']['user'] ?: null;
    }

    public function getDbPassword(): ?string
    {
        return $this->config['db']['password'] ?: null;
    }

    public function getDbOptions(): ?array
    {
        return $this->config['db']['options'] ?: null;
    }

    public function getViewPath(): ?string
    {
        return $this->config['view'] ?: null;
    }

    public function getCsrfTtl(): ?int
    {
        return $this->config['csrf']['ttl'] ?: null;
    }

    public function getCsrfKey(): ?string
    {
        return $this->config['csrf']['key'] ?: null;
    }

    public function getCsrfLength(): ?int
    {
        return $this->config['csrf']['length'] ?: null;
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
