<?php

namespace Tests\Unit\ResponseHeaders;

use Kaspi\ResponseHeaders;
use PHPUnit\Framework\TestCase;

class ResponseHeadersTest extends TestCase
{
    public function testSetGet(): void
    {
        $headers = new ResponseHeaders();
        $headers->set('x-header', 'abc');
        $headers->set('x-header-2', 'dfg');

        $this->assertEquals(
            ['x-header' => 'abc', 'x-header-2' => 'dfg'],
            $headers->get()
        );
    }

    public function testGetEmpty(): void
    {
        $headers = new ResponseHeaders();

        $this->assertEquals([], $headers->get());
    }

    public function testReset(): void
    {
        $headers = new ResponseHeaders();
        $headers->set('x-header', 'abc');

        $this->assertEquals(['x-header' => 'abc'], $headers->get());

        $headers->reset();

        $this->assertEquals([], $headers->get());
    }
}
