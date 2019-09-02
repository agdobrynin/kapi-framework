<?php

namespace Kaspi;

class Controller
{
    /** @var Container|null */
    protected $container;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;

    public function __construct(Request $request, Response $response, ?Container $container = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    public function pathFor(string $routeName, ?array $args = null): ?string
    {
        /** @var Router $router */
        $router = $this->container->{Router::class};

        return $router->getRoutePatternByName($routeName, $args);
    }
}
