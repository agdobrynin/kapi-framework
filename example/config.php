<?php
// Пример файла конфигурации который закидывается в конструктор Kaspi::Config
return [
    // отображать Trace Stack вызова при возникновении Exception
    'displayErrorDetails' => true,
    // настройка для \PDO
    'db' => [
        // в примере использовалась компонента symfony/dotenv
        'dsn' => getenv('DB_PDO'),
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASS') ?: '',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
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
