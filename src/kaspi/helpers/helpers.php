<?php

use Kaspi\FlashMessages;
use Kaspi\Router;

if (false === function_exists('flashErrors')) {
    function flashErrors(): ?array
    {
        return FlashMessages::display(FlashMessages::ERROR);
    }
}

if (false === function_exists('flashSuccess')) {
    function flashSuccess(): ?array
    {
        return FlashMessages::display(FlashMessages::SUCCESS);
    }
}

if (false === function_exists('flashWarning')) {
    function flashWarning(): ?array
    {
        return FlashMessages::display(FlashMessages::WARNING);
    }
}

if (false === function_exists('getCurrentRoute')) {
    function getCurrentRoute(): ?string
    {
        return Router::getCurrentRouteName();
    }
}

if (false === function_exists('isRoute')) {
    function isRoute(string $route): bool
    {
        return $route === getCurrentRoute();
    }
}
