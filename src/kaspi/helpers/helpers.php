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

if (false === function_exists('getCurrentRouteName')) {
    // возвращает выолняемое имя роута (если он именован)
    function getCurrentRouteName(): ?string
    {
        return Router::getCurrentRouteName();
    }
}

if (false === function_exists('isRouteName')) {
    // проверяет имя роута с выполняемым именем роута (если он именован)
    function isRouteName(string $routeName): bool
    {
        return $routeName === getCurrentRouteName();
    }
}
