# Заметки по разработке бандла 

## docker
Настроить в `/.devilbox/apache24.yml` правильный DocumentRoot (пример лежит в `/other/apache24.yml`)

Настроить правильный  `/cfg/apache-2.4/vhost_telegram.conf` (пример лежит в `/other/vhost_telegram.conf`)

## База данных, миграции
Для отслеживания изменений бд с помощью миграций добавить в родительский `config/packages/doctrine.yaml`
```
doctrine:  
	...
    orm:       
		...
        mappings:
            ...
            JustCommunication\TelegramBundle:
                is_bundle: false
                dir: '%kernel.project_dir%/bundles/JustCommunication/TelegramBundle/src/Entity'
                prefix: 'JustCommunication\TelegramBundle\Entity'
                alias: JustCommunication\TelegramBundle
```
И изменения в бандле будут автоматом подтягиваться, супер!
php bin/console make:migration

есть еще, но не пробовал:
```
php bin/console make:entity --regenerate "App\Entity\NewsTop"
```

##Тесты

Тесты находятся внутри бандла, но расчитаны на запуск из хост-проекта, поэтому все настройки окружения для тестирования необходимо проделать самому.
Тесты написаны с таким расчетом, что будут запускаться на рабочей базе, поэтому требуют наличия данных, изменяют эти данные, но возвращают назад.

Запуск:

```php bin/phpunit bundles/JustCommunication/TelegramBundle/tests```

или

```php bin/phpunit vendor/justcommunication/telegram-bundle/tests```
в зависимости от подключения бандла.

Запуск одного теста:

```php bin/phpunit bundles/JustCommunication/TelegramBundle/tests/RepositoryTest.php --filter testTelegramEventsExist```

Замечание: При смене конфигов не забыть выполнить `php bin/console cache:clear --env test`

###Тесты изнутри

Можно попробовать запускать тесты изнутри, но там свои нюансы 
php vendor/bin/simple-phpunit tests




# ДОРАБОТАТЬ

* @todo Логику общения с пользователем надо тоже вынести из контроллера в вебхук, тут оставить только передачу параметров и вывод ответа
* @todo *Comand методы должны возвращать строго строку
* @todo WebhookInterface придумать
* @todo сделать таблицу emoji констант