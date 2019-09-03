<?php

// Пример файла конфигурации который закидывается в конструктор Kaspi::Config
return [
    // отображать Trace Stack вызова при возникновении Exception
    'displayErrorDetails' => true,
    // настройка для \PDO
    'db' => [
        'dsn' => 'sqlite:'.__DIR__.'/../store/db.db',
        'user' => '',
        'password' => '',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ],
        /* миграции в БД vendor/bin/kaspi-migration --help */
        'migration' => [
            /* куда складывать и откуда файлы миграций */
            'path' => __DIR__.'/../migration',
            /* имя таблицы миграций, не обязательное поле, по умолчанию будет таблицу migration */
            'table' => 'migration',
        ],
    ],
    // настройки для шаблонов
    'view' => [
        'path' => __DIR__.'/../app/view',
        // использовать расширение файла при указании имени шаблона? .php
        'useExtension' => false,
    ],
    // защита форм через CSRF
    'csrf' => [
        // срок жизни токена для CSRF защиты форм, TTL
        'ttl' => 1800,
        // какое имя поля должно быть в форме чтобы милвара могла проверить токен
        'name' => 'xCsrf',
        // длина ключа в символах
        'length' => 32,
    ],
    'router' => [
        // образать кончный слешь в роуте - т.е. роуты /maypage и /mypage/ будут идентичны для проутера
        'trailingSlash' => true,
    ],
    // тайм зона приложения
    'default_timezone' => 'Europe/Samara',
    // локаль приложения
    'locale' => [
        'category' => LC_ALL,
        // может быть массивом, может быть строкой
        'locale' => ['ru_RU', 'RU_RU'],
    ],
];
