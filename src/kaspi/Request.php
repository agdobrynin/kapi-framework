<?php

namespace Kaspi;

class Request
{
    /** @var array */
    protected $request;
    /** @var array */
    protected $headers = [];
    /** @var string */
    protected $uri;
    /** @var string */
    protected $requestMethod;
    protected $attributes = [];

    public const METHOD_POST = 'post';
    public const METHOD_GET = 'get';
    public const METHOD_AVAILABLE = [
        self::METHOD_GET,
        self::METHOD_POST,
    ];

    public function __construct()
    {
        $this->request = array_merge(
            $_POST,
            $_GET,
            $_SERVER
        );
        $this->uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $this->requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $this->headers = getallheaders() ?: [];
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

    public function getParam(string $key): ?string
    {
        return $this->request[$key] ?? null;
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
        return $this->headers[$header] ?? null;
    }

    public function uri(): string
    {
        return $this->uri;
    }
}
