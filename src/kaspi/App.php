<?php

namespace Kaspi;

use Kaspi\Exception\AppException;
use Kaspi\Exception\ContainerException;
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
    private static $config;
    /** @var Router */
    private $router;

    public function __construct(Config $config, ?Request $request = null, ?Response $response = null, ?Container $container = null)
    {
        self::$config = $config;

        if (null === $request) {
            $request = new Request();
        }
        $this->request = $request;

        if (null === $response) {
            $response = new Response();
        }
        $this->response = $response;

        if (null === $container) {
            $container = new Container();
        }
        $this->container = $container;
        
        // Router помещаем в контейнер чтобы можно было использовать его например в контроллерах и милварах
        $container = $this->container;
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
        setlocale(self::$config->getLocaleCategory(), ...self::$config->getLocale());
    }

    public function setTimeZone(): void
    {
        date_default_timezone_set(self::$config->getTimeZone());
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public static function getConfig(): Config
    {
        return self::$config;
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
        if (!self::getConfig()->displayErrorDetails()) {
            $traceAsString = '';
        } else {
            $traceAsString = '<br>' . $class . PHP_EOL . '<pre>' . $traceAsString . '</pre>' . PHP_EOL;
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
        } catch (\Exception $exception) {

            $this->response->resetHeaders();
            $this->response->resetBody();

            if ($this->container->has('notFoundHandler') && get_class($exception) === NotFound::class) {

                $this->response->errorHeader(ResponseCode::NOT_FOUND);
                $this->container->get('notFoundHandler', $exception);

            } elseif ($this->container->has('notAllowedHandler') && get_class($exception) === MethodNotAllowed::class) {

                $this->response->errorHeader(ResponseCode::METHOD_NOT_ALLOWED);
                $this->container->get('notAllowedHandler', $exception);

            } elseif ($this->container->has('errorHandler') && 0 === strpos(get_class($exception), Exception\Core::class)) {

                $this->response->errorHeader(ResponseCode::INTERNAL_SERVER_ERROR);
                $this->container->get('errorHandler', $exception);

            } elseif ($this->container->has('phpHandler')) {

                $this->response->errorHeader(ResponseCode::INTERNAL_SERVER_ERROR);
                $this->container->get('phpHandler', $exception);

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
            $requestTimeFloat = (float)str_replace(',', '.', $this->request->getEnv('REQUEST_TIME_FLOAT'));
            if ($time = (microtime(true) - $requestTimeFloat)) {
                $this->response->setHeader('X-Generation-time', $time);
            }
            echo $this->response->emit();
        }
    }
}
