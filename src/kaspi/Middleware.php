<?php

namespace Kaspi;

class Middleware
{
    protected $request;
    protected $response;
    protected $container;
    protected $next;

    public function __construct(Request $request, Response $response, Container $container, callable $next)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
        $this->next = $next;
    }
}
