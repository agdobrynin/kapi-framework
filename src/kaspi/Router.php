<?php

namespace Kaspi;

use Kaspi\Exception\RouterException;

final class Router
{
    /** @var array */
    private $routes;
    private static $currentRouteName;
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
    /** @var Config */
    private $config;

    private const ROUTE_METHOD = 'ROUTE_METHOD';
    private const ROUTE_ACTION = 'ROUTE_ACTION';
    private const ROUTE_NAME = 'ROUTE_NAME';

    public function __construct(Request $request, Response $response, ?Container $container = null)
    {
        $this->routes = [];
        $this->middleware = [];
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
        $this->config = App::getConfig();
    }

    public static function getCurrentRouteName(): ?string
    {
        return self::$currentRouteName;
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

    public function put(string $route, $callable): self
    {
        $this->add($route, $callable, $this->request::METHOD_PUT);

        return $this;
    }

    public function delete(string $route, $callable): self
    {
        $this->add($route, $callable, $this->request::METHOD_DELETE);

        return $this;
    }

    public function patch(string $route, $callable): self
    {
        $this->add($route, $callable, $this->request::METHOD_PATCH);

        return $this;
    }

    public function any(string $route, $callable): self
    {
        $this->add($route, $callable);

        return $this;
    }

    /**
     * Добмавить мидлвару к роуту или глобальную.
     */
    public function middleware($callable, ?string $route = null): self
    {
        // глобавльная мидлвара
        if ('' === $route) {
            $lastRoute = '';
            $next = static function () {
            };
        } else {
            $lastRoute = key(array_slice($this->routes, -1, 1, true));
            $next = $this->routes[$lastRoute][self::ROUTE_ACTION];
        }
        if (is_string($callable)) {
            if (!class_exists($callable)) {
                throw new RouterException(sprintf('Middleware `%s` not defined', $callable));
            }
            $callable = new $callable($this->request, $this->response, $this->container, $next);
        }

        if (!is_callable($callable)) {
            throw new RouterException(sprintf('Middleware `%s` is not callable', $callable));
        }

        $this->middleware[$lastRoute][] = $callable;

        return $this;
    }

    /**
     * Добавить имя роуту.
     */
    public function name(string $name): self
    {
        if (!empty($name)) {
            $lastRoute = key(array_slice($this->routes, -1, 1, true));
            $this->routes[$lastRoute][self::ROUTE_NAME] = $name;
        }

        return $this;
    }

    /**
     * @param string      $route         может быть роут с регулярными выражениями
     * @param mixed       $callable      дейтвие
     * @param string      $requestMethod request method
     * @param string|null $name          имя роута
     *
     * @throws RouterException
     */
    public function add(string $route, $callable, ?string $requestMethod = '', ?string $name = null): void
    {
        // Проверка на добавление только разрешенные методы запросов
        if (!$this->request->isValidRequestMethod()) {
            throw new RouterException(
                sprintf('Request method `%s` is not support', $requestMethod),
                ResponseCode::METHOD_NOT_ALLOWED
            );
        }
        // контроллер
        if (is_string($callable)) {
            if (false !== strpos($callable, $this->defaultActionSymbol)) {
                $controller = strstr($callable, $this->defaultActionSymbol, true);
                $method = substr(strrchr($callable, $this->defaultActionSymbol), 1);
                if (!class_exists($controller)) {
                    throw new RouterException(sprintf('Сontroller `%s` not defined', $controller));
                }
                if (!method_exists($controller, $method)) {
                    throw new RouterException(sprintf('Method `%s` at controller `%s` not defined', $method, $controller));
                }
                $callableResult = [new $controller($this->request, $this->response, $this->container), $method];
            } else {
                if (!class_exists($callable)) {
                    throw new RouterException(sprintf('Controller `%s` not defined', $callable));
                }
                $callableResult = new $callable($this->request, $this->response, $this->container);
            }
        } else {
            $callableResult = $callable;
        }

        if (!is_callable($callableResult)) {
            throw new RouterException(sprintf('Controller `%s` is not callable', $callable));
        }
        $this->routes[$route] = [
            self::ROUTE_METHOD => $requestMethod,
            self::ROUTE_ACTION => $callableResult,
            self::ROUTE_NAME => $name,
        ];
    }

    private function resolveMiddlewareGlobal(string $route): ?bool
    {
        $globalMiddleware = $this->middleware[''] ?? [];
        foreach ($globalMiddleware as $middleware) {
            if (is_callable($middleware)) {
                $next = $this->routes[$route][self::ROUTE_ACTION];
                $callable = new $middleware($this->request, $this->response, $this->container, $next);
                if ($res = $callable()) {
                    return true;
                }
            }
        }

        return null;
    }

    private function resolveMiddleware(string $route): ?bool
    {
        if ($middleware = $this->middleware[$route] ?? null) {
            self::$currentRouteName = $this->routes[$route][self::ROUTE_NAME];
            if (is_callable($middleware[0])) {
                if ($res = call_user_func($middleware[0])) {
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * @throws RouterException
     */
    public function resolve(): void
    {
        // настройка конечный слеш в uri опцилнально
        $trailingSlash = $this->config->getTrailingSlash() ? '/?' : '';
        foreach ($this->routes as $route => $action) {
            if (1 === preg_match('@^'.$route.$trailingSlash.'$@D', $this->request->uri(), $matches)) {
                $isValidRout = empty($action[self::ROUTE_METHOD]) || $this->request->getRequestMethod() === $action[self::ROUTE_METHOD];
                if ($isValidRout) {
                    self::$currentRouteName = $action[self::ROUTE_NAME];
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
                    call_user_func_array($action[self::ROUTE_ACTION], $params);

                    return;
                }
            }
        }
        if (isset($isValidRout)) {
            throw new RouterException(
                sprintf('Method not allowed at route %s', $this->request->uri()),
                ResponseCode::METHOD_NOT_ALLOWED
            );
        }
        throw new RouterException(
            sprintf('Route %s not resolved', $this->request->uri()),
            ResponseCode::NOT_FOUND
        );
    }
}
