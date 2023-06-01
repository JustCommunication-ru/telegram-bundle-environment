symfony new telegram --version="6.3.*@dev"
composer require symfony/orm-pack
composer require doctrine/annotations
composer require symfony/maker-bundle --dev

создание composer.json
*Автолоад переписать на "JustCommunication\\Telegram\\": "src/", Изменить в Kernel, bin/console, src/index.php

php bin/console doctrine:database:create

config/packages/doctrine.yaml


php bin/console make:migration
