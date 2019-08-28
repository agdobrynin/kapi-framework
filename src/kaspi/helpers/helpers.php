<?php
namespace Kaspi\helpers;

use Kaspi\FlashMessages;
use Kaspi\Router;

if (false === function_exists('Kaspi\helpers\flashMessages')) {
    function flashMessages(): FlashMessages
    {
        return new FlashMessages();
    }
}

if (false === function_exists('Kaspi\helpers\getCurrentRoute')) {
    function getCurrentRoute(): ?string {
        return Router::getCurrentRouteName();
    }
}

if (false === function_exists('Kaspi\helpers\isRoute')) {
    function isRoute(string $route): bool {
        return $route === getCurrentRoute();
    }
}
