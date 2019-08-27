<?php

namespace Kaspi;

class ResponseBody
{
    protected $body;

    public function set(string $body)
    {
        $this->body .= $body;
    }

    public function get(): ?string
    {
        return $this->body;
    }
}
