<?php

namespace Kaspi;

final class FlashMessages
{
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const FROM_VALIDATOR = 'fromValidator';

    private const FlashNamespace = '_FLASH_MESSAGES_';

    public static function addFormValidator(string $value, ?string $key = null): void
    {
        self::add($value, self::FROM_VALIDATOR, $key);
    }

    public static function addSuccess(string $value, ?string $key = null): void
    {
        self::add($value, self::SUCCESS, $key);
    }

    public static function addInfo(string $value, ?string $key = null): void
    {
        self::add($value, self::INFO, $key);
    }

    public static function addWarning(string $value, ?string $key = null): void
    {
        self::add($value, self::WARNING, $key);
    }

    public static function addError(string $value, ?string $key = null): void
    {
        self::add($value, self::ERROR, $key);
    }

    public static function add(string $value, string $type, ?string $key = null): void
    {
        $arrMessages = $_SESSION[self::FlashNamespace] ?? [];
        if ($key) {
            $arrMessages[$type][$key] = $value;
        } else {
            $arrMessages[$type][] = $value;
        }
        $_SESSION[self::FlashNamespace] = $arrMessages;
    }

    public static function display(string $type): ?array
    {
        $arrMessages = $_SESSION[self::FlashNamespace] ?? [];
        if (!empty($arrMessages[$type])) {
            $displayMessages = $arrMessages[$type];
            unset($arrMessages[$type]);
            $_SESSION[self::FlashNamespace] = $arrMessages;

            return $displayMessages;
        }

        return null;
    }

    public static function displayAsObjects(string $type): ?object
    {
        return (object)json_decode(
            json_encode(self::display($type)),
            false
        );
    }
}
