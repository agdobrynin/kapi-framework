<?php

namespace Kaspi;

final class FlashMessages
{
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    private const FlashNamespace = '_FLASH_MESSAGES_';

    public static function add(string $value, string $type): void
    {
        if ($arrMessages = $_SESSION[self::FlashNamespace] ?? null) {
            $arrMessages[$type][] = $value;
        } else {
            $arrMessages[$type][] = $value;
        }
        $_SESSION[self::FlashNamespace] = $arrMessages;
    }

    public static function has(?string $type = null): bool
    {
        $arrMessages = $_SESSION[self::FlashNamespace] ?? [];
        if (null === $type) {
            return count($arrMessages) > 0;
        }

        return isset($arrMessages[$type]) && count($arrMessages[$type]) > 0;
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
}
