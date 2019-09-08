<?php

namespace Kaspi;

use Kaspi\Exception\AppErrorHandler;
use Kaspi\Exception\Core\AppException;
use Kaspi\Exception\Core\ContainerException;
use Kaspi\Exception\Router\MethodNotAllowed;
use Kaspi\Exception\Router\NotFound;
use function date_default_timezone_set;
use function setlocale;

class App
{
    public const APP_PROD = 'PROD';
    public const APP_DEV = 'DEV';
    /** @var Request входящий http запрос */
    public $request;
    /** @var Response ответ http */
    public $response;
    /** @var Container|null */
    public $container;
    /** @var Config */
    public $config;
    /** @var Router */
    private $router;

    public function __construct(Config $config, ?Request $request = null, ?Response $response = null, ?Container $container = null)
    {
        if (null === $container) {
            $container = new Container();
        }
        $this->container = $container;

        $this->config = $config;
        $this->container->set(Config::class, static function () use ($config) {
            return $config;
        });

        if (null === $request) {
            $request = new Request();
        }
        $this->request = $request;
        $this->container->set(Request::class, static function () use ($request) {
            return $request;
        });

        if (null === $response) {
            $response = new Response();
        }
        $this->response = $response;
        $this->container->set(Response::class, static function () use ($response) {
            return $response;
        });

        // Router помещаем в контейнер чтобы можно было использовать его например в контроллерах и милварах
        try {
            $this->container->set(Router::class, static function () use ($request, $response, $container): Router {
                return new Router($request, $response, $container);
            });
            $this->router = $this->container->get(Router::class);
        } catch (ContainerException $exception) {
            throw new AppException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setLocale(): void
    {
        setlocale($this->config->getLocaleCategory(), ...$this->config->getLocale());
    }

    public function setTimeZone(): void
    {
        date_default_timezone_set($this->config->getTimeZone());
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function uri(): string
    {
        return $this->request->uri();
    }

    public function get(string $route, $callable): Router
    {
        return $this->router->get($route, $callable);
    }

    public function post($route, $callable): Router
    {
        return $this->router->post($route, $callable);
    }

    public function put($route, $callable): Router
    {
        return $this->router->put($route, $callable);
    }

    public function patch($route, $callable): Router
    {
        return $this->router->patch($route, $callable);
    }

    public function delete($route, $callable): Router
    {
        return $this->router->delete($route, $callable);
    }

    public function any($route, $callable): Router
    {
        return $this->router->any($route, $callable);
    }

    public function middleware($callable): Router
    {
        return $this->router->middleware($callable);
    }

    public function exceptionTemplate(string $responsePhrase, string $message, string $traceAsString, string $class): string
    {
        if (!$this->config->displayErrorDetails()) {
            $traceAsString = '';
        } else {
            $traceAsString = '<br>'.$class.PHP_EOL.'<pre>'.$traceAsString.'</pre>'.PHP_EOL;
        }

        return <<< EOF
                <html><head>
                <title>{$responsePhrase}</title>
                </head><body>
                <h1>{$responsePhrase}</h1>
                <p>{$message}</p>
                {$traceAsString}
                </body></html>
EOF;
    }

    public function run(): void
    {
        try {
            $this->router->resolve();
        } catch (\Throwable $exception) {
            $this->response->resetHeaders();
            $this->response->resetBody();

            if ($this->container->has(AppErrorHandler::NOT_FOUND) && NotFound::class === get_class($exception)) {
                $this->response->errorHeader(ResponseCode::NOT_FOUND);
                $this->container->get(AppErrorHandler::NOT_FOUND, $exception);
            } elseif ($this->container->has(AppErrorHandler::NOT_ALLOWED) && MethodNotAllowed::class === get_class($exception)) {
                $this->response->errorHeader(ResponseCode::METHOD_NOT_ALLOWED);
                $this->container->get(AppErrorHandler::NOT_ALLOWED, $exception);
            } elseif ($this->container->has(AppErrorHandler::CORE_ERROR) && 0 === strpos(get_class($exception), Exception\Core::class)) {
                $this->response->errorHeader(ResponseCode::INTERNAL_SERVER_ERROR);
                $this->container->get(AppErrorHandler::CORE_ERROR, $exception);
            } elseif ($this->container->has(AppErrorHandler::PHP_ERROR) && false === strpos(get_class($exception), __NAMESPACE__)) {
                $this->response->errorHeader(ResponseCode::INTERNAL_SERVER_ERROR);
                $this->container->get(AppErrorHandler::PHP_ERROR, $exception);
            } else {
                $exceptionCode = $exception->getCode() ?: ResponseCode::INTERNAL_SERVER_ERROR;
                $exceptionMessage = $exception->getMessage();
                $traceAsString = $exception->getTraceAsString();

                $this->response->errorHeader($exceptionCode);
                $body = $this->exceptionTemplate(
                    ResponseCode::PHRASES[$exceptionCode],
                    $exceptionMessage,
                    $traceAsString,
                    get_class($exception)
                );
                $this->response->setBody($body);
            }
        } finally {
            $requestTimeFloat = (float) str_replace(',', '.', $this->request->getEnv('REQUEST_TIME_FLOAT'));
            if ($time = (microtime(true) - $requestTimeFloat)) {
                $this->response->setHeader('X-Generation-time', $time);
            }
            $this->response->setHeader('X-memory-usage-kByte', round(memory_get_usage() / 1024));
            $this->response->setHeader('X-memory-peak-usage-kByte', round(memory_get_peak_usage() / 1024));
            echo $this->response->emit();
        }
    }
}
