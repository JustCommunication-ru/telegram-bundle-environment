symfony new telegram --version="6.3.*@dev"
composer require symfony/orm-pack
composer require doctrine/annotations
composer require symfony/maker-bundle --dev

создание composer.json
*Автолоад переписать на "JustCommunication\\Telegram\\": "src/", Изменить в Kernel, bin/console, src/index.php




#ЗАПУСК ПРОЕКТА

Запустить `composer update`

Настроить .env.local
1) подключение к бд `DATABASE_URL`
2) Настройки подключения к телеграму `JC_TELEGRAM__*` Как создать бота отдельная тема.


Если база данных в проекте отсутствует, то выполнить (будет создана telegram):

```php bin/console doctrine:database:create```

```php bin/console doctrine:migration:migrate```

Установка config/packages/telegram.yaml
```php bin/console jc:telegram:install```


Нужно переписать InstalCommand


config/packages/doctrine.yaml

php bin/console make:migration

Настроить в `/.devilbox/apache24.yml` правильный DocumentRoot

Добавить в родительский `config/packages/doctrine.yaml`
```
doctrine:
    orm:        
        mappings:            
            JustCommunication\TelegramBundle:
                is_bundle: false
                dir: '%kernel.project_dir%/bundles/JustCommunication/TelegramBundle/src/Entity'
                prefix: 'JustCommunication\TelegramBundle\Entity'
                alias: JustCommunication\TelegramBundle
```
И изменения в бандле будут автоматом подтягиваться, супер!
есть еще, но не пробовал:
```
php bin/console make:entity --regenerate "App\Entity\NewsTop"
```


# Роуты
```
telegram_bundle:
    resource: '@TelegramBundle/config/routes.yaml'
    prefix: /telegram #здесь можно указать любой префикс, по умолчанию бандл использует пути "/telegram/..."
    name_prefix:  # не используйте префикс имен, иначе некоторые функции рабоать не будут
```


Тесты запускать из бандла
php vendor/bin/simple-phpunit tests


Бандл использует стандартный security и расчитывает на то что в хост проекте есть \App\Entity\User у которого есть test/json поле roles.
Поддерживает роли ROLE_ADMINISTRATOR, ROLE_SUPERUSER, ROLE_MANAGER и все остальные. При этом конвертирует их во внутренние Superuser, Manager и User соответственно. Вопрос зачем такие ограничения? надо просто пробрасывать роли как есть наверно



#Тесты

Тесты находятся внутри бандла, но расчитаны на запуск из хост-проекта, поэтому все настройки окружения для тестирования необходимо проделать самому.
Тесты написаны с таким расчетом, что будут запускаться на боевой базе, поэтому требовать наличия данных, изменяют эти данные, но возвращают назад.

Запуск: 

```php bin/phpunit bundles/JustCommunication/TelegramBundle/tests```

или 

```php bin/phpunit vendor/justcommunication/telegram-bundle/tests```
в зависимости от подключения бандла.

Запуск одного теста:

```php bin/phpunit bundles/JustCommunication/TelegramBundle/tests/RepositoryTest.php --filter testTelegramEventsExist```

Замечание: При смене конфигов не забыть выполнить `php bin/console cache:clear --env test`


# ДОРАБОТАТЬ
@todo вынести в отдельные бандлы:
 - CacheHelper
 - SmsAeroHelper
 - RedisHelper
