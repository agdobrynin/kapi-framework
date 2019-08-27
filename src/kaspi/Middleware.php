<?php

namespace Kaspi;

class Middleware
{
    private $request;
    private $response;
    private $container;
    private $next;

    public function __construct(Request $request, Response $response, Container $container, callable $next)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
        $this->next = $next;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function getNext(): callable
    {
        return $this->next;
    }
}
