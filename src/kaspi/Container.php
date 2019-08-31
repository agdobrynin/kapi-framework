<?php

namespace Kaspi;

use Kaspi\Exception\ContainerException;

class Container
{
    private $container;
    private $resolvedContainer;

    public function __construct()
    {
        $this->container = [];
    }

    public function __get(string $name)
    {
        $this->get($name);
    }

    public function __set(string $name, $container)
    {
        $this->set($name, $this->container);
    }

    public function set(string $name, $container): void
    {
        if ($this->has($name)) {
            throw new ContainerException("Container {$name} already registered");
        }
        $this->container[$name] = $container;
    }

    public function get(string $name)
    {
        if (!empty($this->container[$name])) {
            if (empty($this->resolvedContainer[$name])) {
                $this->resolvedContainer[$name] = $this->container[$name]();
            }

            return $this->resolvedContainer[$name];
        }
        throw new ContainerException("Container '{$name}' not registered");
    }

    public function has(string $name): bool
    {
        return isset($this->container[$name]);
    }
}
