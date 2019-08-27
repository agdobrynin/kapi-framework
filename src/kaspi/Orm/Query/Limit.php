<?php

namespace Kaspi\Orm\Query;

class Limit
{
    protected $page;
    protected $pageSize;

    public function __construct(int $page = 1, int $pageSize = 10)
    {
        $this->page = $page < 1 ? 1 : $page;
        $this->pageSize = $pageSize < 1 ? 1 : $pageSize;
    }

    public function __toString(): string
    {
        $offset = ($this->page - 1) * $this->pageSize;

        return 'LIMIT '.$offset.', '.$this->pageSize;
    }
}
