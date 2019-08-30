<?php

namespace Kaspi\Components;

use Kaspi\Config;
use Kaspi\Exception\CsrfGuardException;
use Kaspi\Request;

class CsrfGuard
{
    /** @var string имя токена (ключ) */
    private $tokenName;
    /** @var string текущее значение токена */
    private $tokenValue;
    /** @var int дина токена */
    private $strength;
    /** @var int время истечения токена */
    private $ttl;

    public function __construct(?Config $config = null)
    {
        // дефолтные зданчения
        $this->tokenName = 'xCsrf';
        $this->strength = 32;
        $this->ttl = 1800;

        if (null !== $config) {
            if ($tokenName = $config->getCsrfName()) {
                $this->tokenName = $tokenName;
            }
            if ($strength = $config->getCsrfLength()) {
                $this->strength = $strength;
            }
            if ($ttl = $config->getCsrfTtl()) {
                $this->ttl = $ttl;
            }
        }
    }

    public function verify(Request $request)
    {
        // Реагировать на Csrf защиту надо методам которые могут изменить данные в приложении.
        $isNeedCheck = in_array(
            $request->getRequestMethod(),
            [Request::METHOD_POST, Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_DELETE],
            true
        );
        if ($isNeedCheck) {
            $token = (string) $request->getParam($this->tokenName) ?: $request->getHeader($this->tokenName);
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
        return $this->tokenName;
    }

    private function createToken(): string
    {
        $token = bin2hex(random_bytes($this->strength));
        $_SESSION[$this->tokenName] = ['token' => $token, 'ttl' => time() + $this->ttl];

        return $token;
    }

    private function isValidToken(?string $token): void
    {
        // TODO проверять наличие SESSION или еще лучше перейти на класс декоратор типа SessionStorage
        if ($_SESSION[$this->tokenName]['ttl'] < time()) {
            throw new CsrfGuardException('Csrf token key is expired');
        }
        if ($_SESSION[$this->tokenName]['token'] !== $token) {
            throw new CsrfGuardException('Csrf token key is wrong');
        }
    }
}
