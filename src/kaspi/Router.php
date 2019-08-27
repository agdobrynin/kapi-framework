<?php

namespace Kaspi;

use Kaspi\Exception\AppException;
use Kaspi\Exception\RouterException;

final class Router
{
    /** @var array */
    private $routes;
    /** @var array */
    private $middleware;
    /** @var string */
    private $defaultActionSymbol = '@';
    /** @var Request */
    private $request;
    /** @var Response */
    private $response;
    /** @var Container|null */
    private $container;

    public function __construct(Request $request, Response $response, ?Container $container = null)
    {
        $this->routes = [];
        $this->middleware = [];
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
        $this->config = App::getConfig();
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function get(string $route, $callable): self
    {
        $this->add($route, $callable, $this->request::METHOD_GET);

        return $this;
    }

    public function post(string $route, $callable): self
    {
        $this->add($route, $callable, $this->request::METHOD_POST);

        return $this;
    }

    public function any(string $route, $callable): self
    {
        $this->add($route, $callable);

        return $this;
    }

    public function middleware($callable, ?string $route = null): self
    {
        // глобавльная мидлвара
        if ('' === $route) {
            $key = '';
            $next = static function () {
            };
        } else {
            $key = key(array_slice($this->routes, -1, 1, true));
            $next = $this->routes[$key]['controller'];
        }
        if (is_string($callable)) {
            $callable = new $callable($this->request, $this->response, $this->container, $next);
        }
        $this->middleware[$key][] = $callable;

        return $this;
    }

    /**
     * @param string $route         может быть роут с регулярными выражениями
     * @param mixed  $callable      дейтвие
     * @param string $requestMethod request method
     *
     * @throws AppException
     */
    public function add(string $route, $callable, ?string $requestMethod = ''): void
    {
        // контроллер
        if (is_string($callable)) {
            if (false !== strpos($callable, $this->defaultActionSymbol)) {
                $controller = strstr($callable, $this->defaultActionSymbol, true);
                $method = substr(strrchr($callable, $this->defaultActionSymbol), 1);
                $callable = [new $controller($this->request, $this->response, $this->container), $method];
            } else {
                $callable = new $callable($this->request, $this->response, $this->container);
            }
        }

        if (!is_callable($callable)) {
            throw new AppException('Action is not callable');
        }
        $this->routes[$route] = [
            'requestMethod' => $requestMethod,
            'controller' => $callable,
        ];
    }

    private function resolveMiddlewareGlobal(string $route): ?bool
    {
        $globalMiddleware = $this->middleware[''] ?? [];
        foreach ($globalMiddleware as $middleware) {
            if (is_callable($middleware)) {
                $next = $this->routes[$route]['controller'];
                $callable = new $middleware($this->request, $this->response, $this->container, $next);
                if ($res = call_user_func($callable)) {
                    return true;
                }
            }
        }

        return null;
    }

    private function resolveMiddleware(string $route): ?bool
    {
        if ($middleware = $this->middleware[$route] ?? null) {
            if (is_callable($middleware[0])) {
                if ($res = call_user_func($middleware[0])) {
                    return true;
                }
            }
        }

        return null;
    }

    public function resolve(): void
    {
        // настройка конечный слеш в uri опцилнально
        $trailingSlash = $this->config->getTrailingSlash() ? '/?' : '';
        foreach ($this->routes as $route => $action) {
            if (1 === preg_match('@^'.$route.$trailingSlash.'$@D', $this->request->uri(), $matches)) {
                $isValidRout = empty($action['requestMethod']) || $this->request->getRequestMethod() === $action['requestMethod'];
                if ($isValidRout) {
                    $params = array_intersect_key(
                        $matches,
                        array_flip(array_filter(array_keys($matches), 'is_string'))
                    );
                    // Установим в Request параметры полученные от роута через regExp переменные
                    $this->request->setAttributes($params);
                    // Глобальные мидлвары
                    if ($res = $this->resolveMiddlewareGlobal($route)) {
                        return;
                    }
                    // Мидлвары привязанные к роуту
                    if ($res = $this->resolveMiddleware($route)) {
                        return;
                    }
                    // Для вызова маршрута с колбэк функциями, удобно для коротких контроллеров rest api
                    call_user_func_array($action['controller'], $params);

                    return;
                }
            }
        }
        if (isset($isValidRout)) {
            throw new RouterException(
                'Method not allowed at route '.$this->request->uri(),
                ResponseCode::METHOD_NOT_ALLOWED
            );
        }
        throw new RouterException(
            'Route '.$this->request->uri().' not resolved',
            ResponseCode::NOT_FOUND
        );
    }
}
