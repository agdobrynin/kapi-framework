<?php

namespace Kaspi\Interfaces;

interface ResponseBodyInterface
{
    public function write(string $body): void;

    public function __toString(): string;

    public function getContents(): string;

    public function getSize(): ?int;

    public function rewind(): void;

    public function open(): void;

    public function close(): void;

    public function detach(): void;
}