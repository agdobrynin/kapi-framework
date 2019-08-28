<?php
namespace Kaspi\helpers;

use Kaspi\FlashMessages;

if (false === function_exists('Kaspi\helpers\flashErrors')) {
    function flashErrors(): ?array
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
