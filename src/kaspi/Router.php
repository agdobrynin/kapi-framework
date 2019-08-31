<?php

namespace Kaspi;

use Kaspi\Exception\RouterException;

final class Router
{
    /** @var array */
    private $routes;
    private static $currentRouteName;
    /** @var array Глобальные милдвары */
    private $middleware;
    /** @var string */
    private $defaultActionSymbol = '@';
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var Container|null */
    protected $container;
    /** @var Config */
    protected $config;

    private const ROUTE_METHOD = 'ROUTE_METHOD';
    private const ROUTE_ACTION = 'ROUTE_ACTION';
    private const ROUTE_NAME = 'ROUTE_NAME';
    private const ROUTE_PATTERN = 'ROUTE_PATTERN';
    private const ROUTE_MIDDLEWARE = 'ROUTE_MIDDLEWARE';

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

    public function get(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable, $this->request::METHOD_GET);

        return $this;
    }

    public function post(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable, $this->request::METHOD_POST);

        return $this;
    }

    public function put(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable, $this->request::METHOD_PUT);

        return $this;
    }

    public function delete(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable, $this->request::METHOD_DELETE);

        return $this;
    }

    public function patch(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable, $this->request::METHOD_PATCH);

        return $this;
    }

    public function any(string $routePattern, $callable): self
    {
        $this->map($routePattern, $callable);

        return $this;
    }

    /**
     * Добмавить мидлвару к роуту или глобальную.
     */
    public function middleware($callable): self
    {
        $calledClass = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? '';
        $isGlobalMiddleware = App::class === $calledClass;
        // глобавльная мидлвара
        if ($isGlobalMiddleware) {
            $lastRoute = '';
            $next = static function () {
            };
        } else {
            end($this->routes);
            $lastRoute = key($this->routes);
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

        if ($isGlobalMiddleware) {
            $this->middleware[] = $callable;
        } else {
            $this->routes[$lastRoute][self::ROUTE_MIDDLEWARE][] = $callable;
        }
        reset($this->routes);

        return $this;
    }

    /**
     * Добавить имя роуту.
     */
    public function setName(string $name): self
    {
        if (!empty($name)) {
            end($this->routes);
            $lastRoute = key($this->routes);
            $this->routes[$lastRoute][self::ROUTE_NAME] = $name;
            reset($this->routes);
        }

        return $this;
    }

    public function getRoutePatternByName(string $routeName, ?array $args = null): ?string
    {
        $key = array_search($routeName, array_column($this->routes, self::ROUTE_NAME), true);
        if ($key !== false) {
            // TODO так как возвращается pattern роутера, то там могут быть regex выражения, подуймай как их менять!
            return $this->routes[$key][self::ROUTE_PATTERN];
        }
        return null;
    }

    /**
     * @param string $routePattern  может быть роут с регулярными выражениями
     * @param mixed $callable       дейтвие
     * @param string $requestMethod request method
     * @param string|null $name     имя роута
     *
     * @throws RouterException
     */
    public function map(string $routePattern, $callable, ?string $requestMethod = '', ?string $name = null): void
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
        $this->routes[] = [
            self::ROUTE_PATTERN => $routePattern,
            self::ROUTE_METHOD => $requestMethod,
            self::ROUTE_ACTION => $callableResult,
            self::ROUTE_NAME => $name,
        ];
    }

    private function resolveMiddlewareGlobal(callable $routeAction): ?bool
    {
        foreach ($this->middleware as $middleware) {
            if (is_callable($middleware)) {
                $callable = new $middleware($this->request, $this->response, $this->container, $routeAction);
                if ($res = $callable()) {
                    return true;
                }
            }
        }

        return null;
    }

    private function resolveMiddleware(?array $arrayCallable): ?bool
    {
        foreach ($arrayCallable as $middleware) {
            if ($res = $middleware()) {
                return true;
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
        foreach ($this->routes as $route) {
            /** @var string $routePattern */
            $routePattern = $route[self::ROUTE_PATTERN];
            /** @var string $routeMethod */
            $routeMethod = $route[self::ROUTE_METHOD];
            /** @var string $routeName */
            $routeName = $route[self::ROUTE_NAME];
            /** @var callable $routeAction */
            $routeAction = $route[self::ROUTE_ACTION];
            /** @var array $routeMiddleware */
            $routeMiddleware = $route[self::ROUTE_MIDDLEWARE] ?? [];
            if (1 === preg_match('@^' . $routePattern . $trailingSlash . '$@D', $this->request->uri(), $matches)) {
                $isValidRout = empty($routeMethod) || $this->request->getRequestMethod() === $routeMethod;
                if ($isValidRout) {
                    self::$currentRouteName = $routeName;
                    $params = array_intersect_key(
                        $matches,
                        array_flip(array_filter(array_keys($matches), 'is_string'))
                    );
                    // Установим в Request параметры полученные от роута через regExp переменные
                    $this->request->setAttributes($params);
                    // Глобальные мидлвары
                    if ($res = $this->resolveMiddlewareGlobal($routeAction)) {
                        return;
                    }
                    // Мидлвары привязанные к роуту
                    if ($res = $this->resolveMiddleware($routeMiddleware)) {
                        return;
                    }
                    // Для вызова маршрута с колбэк функциями, удобно для коротких контроллеров rest api
                    call_user_func_array($routeAction, $params);

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
