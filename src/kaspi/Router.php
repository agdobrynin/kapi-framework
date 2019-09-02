<?php

namespace Kaspi;

use Kaspi\Exception\Router\MethodNotAllowed;
use Kaspi\Exception\Router\NotFound;
use Kaspi\Exception\Core\RouterException;

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

    public function __construct(Request $request, Response $response, Container $container, ?Config $config = null)
    {
        $this->routes = [];
        $this->middleware = [];
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
        $this->config = $config ?: $container->get(Config::class);
    }

    public static function getCurrentRouteName(): ?string
    {
        return self::$currentRouteName;
    }

    public function getRoutePatternByName(string $routeName, ?array $args = null): ?string
    {
        $key = array_search($routeName, array_column($this->routes, self::ROUTE_NAME), true);
        if ($key !== false) {
            // TODO так как возвращается pattern роутера, то там могут быть regex выражения, подуймай как их менять!
            // (?<word>\w+) , (?<id>\d+), (?<zip>([0-9]{6})), (?<isbn>([a-z]{3})-([0-9]{4,6})-([a-z]{3,}))
            return $this->routes[$key][self::ROUTE_PATTERN];
        }
        return null;
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
     * Добавить мидлвару к роуту или глобальную.
     */
    public function middleware($callable): self
    {
        $calledClass = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? '';
        $isGlobalMiddleware = App::class === $calledClass;

        if ($isGlobalMiddleware) {
            $this->middleware[] = $callable;
        } else {
            end($this->routes);
            $lastRoute = key($this->routes);
            $this->routes[$lastRoute][self::ROUTE_MIDDLEWARE][] = $callable;
            reset($this->routes);
        }

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

    /**
     * @param string $routePattern  может быть роут с регулярными выражениями
     * @param mixed $callable       дейтвие
     * @param string $requestMethod request method
     * @param string|null $name     имя роута
     */
    public function map(string $routePattern, $callable, ?string $requestMethod = '', ?string $name = null): void
    {
        $this->routes[] = [
            self::ROUTE_PATTERN => $routePattern,
            self::ROUTE_METHOD => $requestMethod,
            self::ROUTE_ACTION => $callable,
            self::ROUTE_NAME => $name,
        ];
    }

    private function resolveRoute($callable): callable
    {
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

        return $callableResult;
    }

    private function resolveMiddlewareAndExecute(array $arrMiddleware, callable $next): ?bool
    {
        foreach ($arrMiddleware as $middleware) {
            if (is_string($middleware)) {
                if (!class_exists($middleware)) {
                    throw new RouterException(sprintf('Middleware `%s` not defined', $middleware));
                }
                $middleware = new $middleware($this->request, $this->response, $this->container, $next);
            }

            if (!is_callable($middleware)) {
                throw new RouterException(sprintf('Middleware `%s` is not callable', $middleware));
            }
            if (null !== $middleware()) {
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
            /** @var mixed $routeAction */
            $routeAction = $route[self::ROUTE_ACTION];
            /** @var array $routeMiddleware */
            $routeMiddleware = $route[self::ROUTE_MIDDLEWARE] ?? [];
            if (1 === preg_match('@^' . $routePattern . $trailingSlash . '$@D', $this->request->uri(), $matches)) {
                // Проверка разрешенные методы запросов
                $isValidRout = empty($routeMethod) || $this->request->getRequestMethod() === $routeMethod;
                if ($isValidRout) {

                    $routeAction = $this->resolveRoute($routeAction);
                    self::$currentRouteName = $routeName;
                    $params = array_intersect_key(
                        $matches,
                        array_flip(array_filter(array_keys($matches), 'is_string'))
                    );
                    // Установим в Request параметры полученные от роута через regExp переменные
                    $this->request->setAttributes($params);
                    // Глобальные мидлвары
                    if (null !== $this->resolveMiddlewareAndExecute($this->middleware, $routeAction)) {
                        return;
                    }
                    // Мидлвары привязанные к роуту
                    if (null !== $this->resolveMiddlewareAndExecute($routeMiddleware, $routeAction)) {
                        return;
                    }
                    // Для вызова маршрута с колбэк функциями, удобно для коротких контроллеров rest api
                    call_user_func_array($routeAction, $params);

                    return;
                }
            }
        }

        if (isset($isValidRout)) {
            throw new MethodNotAllowed(
                sprintf(
                    'Method %s not allowed at route %s',
                    $this->request->getRequestMethod(),
                    $this->request->uri()
                ),
                ResponseCode::METHOD_NOT_ALLOWED
            );
        }

        throw new NotFound(
            sprintf(
                'The requested resource %s could not be found. Please verify the URI and try again',
                $this->request->uri()
            ),
            ResponseCode::NOT_FOUND
        );
    }
}
