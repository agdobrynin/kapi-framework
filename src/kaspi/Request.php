<?php

namespace Kaspi;

use Kaspi\Exception\RequestException;

class Request
{
    /** @var array */
    protected $request;
    /** @var array */
    private $queryString;
    /** @var array из */
    protected $server;
    /** @var array из */
    protected $cookie;
    /** @var array */
    protected $headers;
    /** @var string */
    protected $uri;
    /** @var string */
    protected $requestMethod;
    protected $attributes = [];

    public const METHOD_POST = 'POST';
    public const METHOD_GET = 'GET';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';

    public const METHOD_AVAILABLE = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
    ];

    public function __construct()
    {
        $this->uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $this->requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
    }

    protected function getRequestInput(): void
    {
        if (!$this->isValidRequestMethod()) {
            throw new RequestException("Method \"{$this->getRequestMethod()}\" in request not implemented");
        }

        // в зависимости от метода запроса GET или POST из глобальных переменных PHP
        if ($this->isPost()) {
            $this->request = $_POST;

            return;
        }

        if ($this->isGet()) {
            $this->request = $_GET;

            return;
        }

        // PUT, PATCH, DELETE
        $this->request = [];
        $inputSource = file_get_contents('php://input');
        parse_str($inputSource, $this->request);
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function isPost(): bool
    {
        return self::METHOD_POST === $this->getRequestMethod();
    }

    public function isGet(): bool
    {
        return self::METHOD_GET === $this->getRequestMethod();
    }

    public function isPut(): bool
    {
        return self::METHOD_PUT === $this->getRequestMethod();
    }

    public function isDelete(): bool
    {
        return self::METHOD_DELETE === $this->getRequestMethod();
    }

    public function isPatch(): bool
    {
        return self::METHOD_PATCH === $this->getRequestMethod();
    }

    public function isValidRequestMethod(): bool
    {
        return in_array($this->getRequestMethod(), self::METHOD_AVAILABLE, false);
    }

    /**
     * получение серверных переменных.
     */
    public function getEnv(string $key): ?string
    {
        // SERVER переменные
        if (null === $this->server) {
            $this->server = $_SERVER;
        }

        return $this->server[$key] ?? null;
    }

    /**
     * Полученные с клиента Cookie.
     */
    public function getCookie(string $key): ?string
    {
        // Cookie переданные с клиента
        if (null === $this->cookie) {
            $this->cookie = $_COOKIE;
        }

        return $this->cookie[$key] ?? null;
    }

    /**
     * @throws RequestException
     */
    public function getParam(string $key): ?string
    {
        if (null === $this->request) {
            $this->getRequestInput();
        }

        return $this->request[$key] ?? null;
    }

    public function getQueryParam(string $key): ?string
    {
        // Отдально запросить Query String так как может существовать совместно с другими методами
        if (null === $this->queryString) {
            $this->queryString = $_GET;
        }

        return $this->queryString[$key] ?? null;
    }

    public function getQueryParams(string ...$keys): ?array
    {
        $result = null;
        foreach ($keys as $key) {
            $result[$key] = $this->getQueryParam($key);
        }

        return $result;
    }

    public function getParams(string ...$keys): ?array
    {
        $result = null;
        foreach ($keys as $key) {
            $result[$key] = $this->getParam($key);
        }

        return $result;
    }

    public function getParamsAsVariable(string ...$keys): ?array
    {
        $result = null;
        foreach ($keys as $key) {
            $result[] = $this->getParam($key);
        }

        return $result;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function setAttributes(array $args): void
    {
        foreach ($args as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function getAttribute(string $attribute): ?string
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function getHeader(string $header): ?string
    {
        if (null === $this->headers) {
            $this->headers = getallheaders() ?: [];
        }

        return $this->headers[$header] ?? null;
    }

    public function uri(): string
    {
        return $this->uri;
    }
}
