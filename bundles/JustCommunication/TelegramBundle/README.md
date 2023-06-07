symfony new telegram --version="6.3.*@dev"
composer require symfony/orm-pack
composer require doctrine/annotations
composer require symfony/maker-bundle --dev

создание composer.json
*Автолоад переписать на "JustCommunication\\Telegram\\": "src/", Изменить в Kernel, bin/console, src/index.php

php bin/console doctrine:database:create

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



Бандл использует стандартный security и расчитывает на то что в хост проекте есть \App\Entity\User у которого есть test/json поле roles.
Поддерживает роли ROLE_ADMINISTRATOR, ROLE_SUPERUSER, ROLE_MANAGER и все остальные. При этом конвертирует их во внутренние Superuser, Manager и User соответственно. Вопрос зачем такие ограничения? надо просто пробрасывать роли как есть наверно