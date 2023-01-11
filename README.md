# Простой фреймворк в виде composer пакета

Реализован MVC паттерн, роутер(router), посредники (middleware), простой контейнер зависимостей (container), простой шаблонизатор и рендеринг шаблонов, простая реализация ORM (entity models), простая реализация миграций для базы данных, минимальная Csrf защита данных передаваемых html-форм.

Установка пакета через composer последнего релиза

````bash
composer require kaspi/kapi-framework 
````

так же можно установить последнюю версию из dev разработки пакета
````bash
composer require kaspi/kapi-framework:dev-master
````

##### Код стайл
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

``composer fixer`` 
