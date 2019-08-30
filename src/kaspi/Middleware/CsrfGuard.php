<?php

namespace Kaspi\Middleware;

use Kaspi\App;
use Kaspi\Container;
use Kaspi\Exception\MiddlewareException;
use Kaspi\Middleware;
use Kaspi\Request;
use Kaspi\Response;

class CsrfGuard extends Middleware
{
    /** @var string имя токена (ключ) */
    private $tokenKey;
    /** @var string текущее значение токена */
    private $tokenValue;
    /** @var int дина токена */
    private $strength;
    /** @var int время истечения токена */
    private $ttl;

    public function __construct(Request $request, Response $response, Container $container, callable $next)
    {
        parent::__construct($request, $response, $container, $next);
        $this->tokenKey = App::getConfig()->getCsrfKey() ?? 'xCsrf';
        $this->strength = App::getConfig()->getCsrfLength() ?? 32;
        $this->ttl = App::getConfig()->getCsrfTtl() ?? 1800;
    }

    public function verify()
    {
        // Реагировать на Csrf защиту надо методам которые могут изменить данные в приложении.
        $isNeedCheck = in_array(
            $this->getRequest()->getRequestMethod(),
            [Request::METHOD_POST, Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_DELETE],
            true
        );
        if ($isNeedCheck) {
            $token = (string) $this->getRequest()->getParam($this->tokenKey) ?: $this->getRequest()->getHeader($this->tokenKey);
            $this->isValidToken($token);
        }

        $this->tokenValue = $this->createToken();
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    public function getTokenName(): string
    {
        return $this->tokenKey;
    }

    private function createToken(): string
    {
        $token = bin2hex(random_bytes($this->strength));
        $_SESSION[$this->tokenKey] = ['token' => $token, 'ttl' => time() + $this->ttl];

        return $token;
    }

    private function isValidToken(?string $token): void
    {
        if ($_SESSION[$this->tokenKey]['ttl'] < time()) {
            throw new MiddlewareException('Csrf token key is expired');
        }
        if ($_SESSION[$this->tokenKey]['token'] !== $token) {
            throw new MiddlewareException('Csrf token key is wrong');
        }
    }
}
