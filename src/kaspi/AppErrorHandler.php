<?php

namespace Kaspi;
/**
 * Имена хэндлеров для обработки Exception-ов
 *
 * Class AppErrorHandler
 * @package Kaspi
 */
class AppErrorHandler
{
    /**
     * не найден роут
     */
    public const NOT_FOUND = 'notFoundHandler';
    /**
     * метод не доступен для выполяемого роута
     */
    public const NOT_ALLOWED = 'notAllowedHandler';
    /**
     * Exception фреймворка - namespace начинается с Kaspi\Exception\Core
     */
    public const CORE_ERROR = 'errorHandler';
    /**
     * Exception выброшенный php
     */
    public const PHP_ERROR = 'phpHandler';
}
