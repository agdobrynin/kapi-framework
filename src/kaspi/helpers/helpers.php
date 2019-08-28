<?php
namespace Kaspi\helpers;

use Kaspi\FlashMessages;
use Kaspi\Router;

if (false === function_exists('Kaspi\helpers\flashErrors')) {
    function flash(): ?array
    {
        return FlashMessages::display(FlashMessages::ERROR);
    }
}

if (false === function_exists('Kaspi\helpers\flashSuccess')) {
    function flashSuccess(): ?array
    {
        return FlashMessages::display(FlashMessages::SUCCESS);
    }
}

if (false === function_exists('Kaspi\helpers\flashWarning')) {
    function flashWarning(): ?array
    {
        return FlashMessages::display(FlashMessages::WARNING);
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
