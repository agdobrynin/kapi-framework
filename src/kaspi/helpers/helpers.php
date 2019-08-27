<?php

use Kaspi\FlashMessages;

function flashErrors(): ?array
{
    return FlashMessages::display(FlashMessages::ERROR);
}

function flashSuccess(): ?array
{
    return FlashMessages::display(FlashMessages::SUCCESS);
}

function flashWarning(): ?array
{
    return FlashMessages::display(FlashMessages::WARNING);
}
