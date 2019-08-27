<?php

namespace Kaspi;

class Controller
{
    /** @var Container|null */
    private $container;
    private $request;
    private $response;

    public function __construct(Request $request, Response $response, ?Container $container = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
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
}
