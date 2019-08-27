<?php
/**
 * В формах с POST методом вызвать переменную <?php echo $xCsrf?>.
 */

namespace Kaspi\Middleware;

use Kaspi\App;
use Kaspi\Container;
use Kaspi\Exception\AppException;
use Kaspi\Exception\ContainerException;
use Kaspi\Middleware;
use Kaspi\Request;
use Kaspi\Response;

class CsrfGuard extends Middleware
{
    /** @var string имя токена (ключ) */
    private $tokenKey;
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

    public function __invoke()
    {
        if ($this->getRequest()->isPost()) {
            $token = (string) $this->getRequest()->getParam($this->tokenKey) ?: $this->getRequest()->getHeader($this->tokenKey);
            $this->isValidToken($token);
        }

        $token = $this->createToken();

        $this->getResponse()->setHeader($this->tokenKey, $token);
        try {
            $this->getContainer()
                ->get('view')
                ->addGlobalData($this->tokenKey, "<input type='hidden' value='{$token}' name='{$this->tokenKey}'>");
        } catch (ContainerException $exception) {
        }
    }

    protected function createToken(): string
    {
        $token = bin2hex(random_bytes($this->strength));
        $_SESSION[$this->tokenKey] = ['token' => $token, 'ttl' => time() + $this->ttl];

        return $token;
    }

    protected function isValidToken(?string $token): void
    {
        if ($_SESSION[$this->tokenKey]['ttl'] < time()) {
            throw new AppException('Csrf token key is expired');
        }
        if ($_SESSION[$this->tokenKey]['token'] !== $token) {
            throw new AppException('Csrf token key is wrong');
        }
    }
}
