<?php

namespace Kaspi;

class ResponseBody
{
    protected $size;
    protected $stream;

    public function __construct()
    {
        $this->stream = fopen('php://temp', '+rb');
    }

    public function write(string $body): void
    {
        if (false === fwrite($this->stream, $body)) {
            throw new \RuntimeException('Could not write to stream');
        }
        $this->size = null;
    }

    public function __toString(): string
    {
        if ('stream' !== get_resource_type($this->stream)) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    public function getContents(): string
    {
        if (false === ($contents = stream_get_contents($this->stream))) {
            throw new \RuntimeException('Could not get contents of stream');
        }
        return $contents;
    }

    public function getSize(): ?int
    {
        if (!$this->size) {
            $stats = fstat($this->stream);
            $this->size = $stats['size'] ?? null;
        }

        return $this->size;
    }

    public function rewind(): void
    {
        if (false === rewind($this->stream)) {
            throw new \RuntimeException('Could not rewind stream');
        }
    }

    public function close(): void
    {
        fclose($this->stream);
        $this->detach();
    }

    public function detach(): void
    {
        $this->stream = null;
        $this->size = null;
    }
}
