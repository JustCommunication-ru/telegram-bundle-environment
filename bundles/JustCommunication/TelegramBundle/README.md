# Telegram Bundle

## Установка 
`composer require justcommunication/telegram-bundle`

## Требования
Пакет встраивается в полноценный Symfony проект и требует ряд общих механизмов:
- настроенное подключение к базе данных
- наличие пользователей в системе и сущность `App\Entity\User`
- стандартный security и сущность `\App\Entity\User` у которой есть test/json поле `roles`.

Поддерживает роли ROLE_ADMINISTRATOR, ROLE_SUPERUSER, ROLE_MANAGER и все остальные. При этом конвертирует их во внутренние Superuser, Manager и User соответственно. В следующий версиях работу с ролями надо будет расширить.


## Подключение
Настроить .env.local своего проекта
APP_NAME="MyProjectName" # название проекта, используется в сообщениях
APP_URL="https://telegram.loc" # ссылка на проект

JC_TELEGRAM_ADMIN_CHAT_ID=789456123 # ваш личный номер в телеграмме
JC_TELEGRAM_BOT_NAME="MY_PROJECT_BOT" # название бота в произвольной фореме (может не совпадать с официально зарегистрированным), но именно это название будет в сообщениях
JC_TELEGRAM_TOKEN="NUMBERS:MANYSYMBOLS" # Токен выданный телеграммом
JC_TELEGRAM_WEBHOOK_APP_URL_FOR_DEV="https://service.espvbprr.jc9.ru" # для инициализации телеграма в локале, используется только в DEV среде


## Регистрация своего бота
Информация не относится к бандлу, но пусть будет подсказка для тех кто первый раз с этим сталкивается (актуально на 2023):


Если надо завести бота, то идем к ботфазеру (в поиске находим `@BotFather`) и выполняем команду `/newbot`.

На самом деле там есть подсказки что к чему и подробный `/help`. Так что главное понять суть, дальше всегда можно сориентироваться.

При создании бота сначала спросят имя (это человекоприятное имя в вольной форме), а потом username, вот там уже есть правило, чтобы заканчивалось на Bot или _bot.

Для начала стоит зайти в поиск телеграма поискать варианты нужного названия, возможно уже всё занято (все контакты который начинаются с @ это боты)

После успешной регистрации будет выдан токен, в общем-то его можно у ботфазера спросить позднее, так что если не сохранить сразу, то не страшно.

Настройка бота:
- BotPic - аватар для бота/setuserpic.
- About -  это то что будет написано если перейти по прямой сслыке https://t.me/NameOfBot
- Description - это то, что будет написано когда первый раз открыл бота.
- DescriptionPicture это изображение которое будет при первом открытии, требует 640x360.

Что нужно помнить, для полноценной работы с ботом необходимо разместить в сети свой обработчик и зарегистрировать по токену ссылку на которую будут приходить все сообщения отправленные боту.

## Настройка и запуск

Если база данных в проекте отсутствует, то выполнить (будет создана telegram):

```php bin/console doctrine:database:create```

Если бд в проекте уже есть, то запускать не нужно.
Далее выполнить миграции (к миграциям проекта добавятся миграции бандла, которые создадут нужные таблицы)

```php bin/console doctrine:migration:migrate```

Для подключения проекта нужно создать файл конфигурации бандла в хост проекте `config/packages/telegram.yaml`, это можно сделать вручную, а можно выполнить команду:

```php bin/console jc:telegram:install```

Если что-то пойдет не так, то актуальную версию файла конфигурации можно найти в самом бандле `@telegram-bundle-folder/config/packages/telegram.yaml`

Кроме этого потребуется "пробросить роуты" из бандла в хост проект, для этого либо в `config/routes.yaml` добавить, либо создать отдельный файл `config/routes/telegram.yaml` 
```
telegram_bundle:
    resource: '@TelegramBundle/config/routes.yaml'
    prefix: /telegram #здесь можно указать любой префикс, по умолчанию бандл использует пути "/telegram/..." поэтому если пути не пересекаются с путями проекта имеет смысл оставить значение пустым
    name_prefix:  # не используйте префикс имен, иначе некоторые функции работать не будут
    # Все роуты бандла начинаются с "jc_telegram_"
```




## Отладка вебхука
С телеграм-ботом можно общаться только в боевом режиме, так как сервис telegram все зарпосы будет отправлять на реально действующий зарегистрированный адрес. Для отладки реакции вебхука в локале можно использовать command

```php bin/console jc:telegram --webhook "/somecomand"```


## Переопределение поведения телеграм бота
Реакция телеграм бота определяется сервисом вебхука который вызывается в контроллере. 
Вместо autowire JustCommunication\TelegramBundle\TelegramWebhook $webhook в контоллере используется явное подключение вебхука через конфиги в services.yaml
```
services:    
    # Регистрируем id сервиса
    jc.service.telegramwebhook:
        class: JustCommunication\TelegramBundle\Service\TelegramWebhook

    # явно определяем значение аргумента $webhook в __construct() контроллера
    JustCommunication\TelegramBundle\Controller\TelegramController:
        arguments:
            $webhook: '@jc.service.telegramwebhook'
```

Соответственно, для того чтобы использовать свой код необходимо:

- скопировать TelegramWebhook.php из бандла в свой хост проект (например в App\Service\)
- переименовать его (например в MyTelegramWebhook.php) изменив название класса
- унаследовать от вебхкука из пакета `extends TelegramWebhook` 
- оставить только те мотоды, которые необходимо переопределить
- добавить свои обработчики команд
- в хост настройках зарегистрировать свой сервис и подключить к контроллеру пакета:

```
services:
    app.service.mytelegramwebhook:
        class: App\Service\MyTelegramWebhook

    JustCommunication\TelegramBundle\Controller\TelegramController:
        arguments:
            $webhook: '@app.service.mytelegramwebhook'
```

### Обработчики телеграм команд
Класс TelegramWebhook содержит набор функций вида `commandNameSuperuserCommand`

Где commandName не зависящее от регистра (camel case для порядку) имя команды которое будет обработано, например `/commandname`
`Superuser` это роль пользователя на которого отзовется обработчик
`Command` зарезервированный суффикс обработчиков

### Параметры команд
После имени команды может быть текст который будет обработан как набор неименованных входных параметров разделенных пробелами, например `/makebutterbrod bread butter cheese`
```
public function makebutterbrodUserCommand($params = []){
    $base = $param[0]; // bread
    $layer = $param[1]; // butter
    $filling =  $param[2]; // cheese
}
```

## Рассылка сообщений. События.
Изначально работа с отправкой сообщений или рассылкой задумывалась с использованием хелпера.

С помощью autowire получаем доступ к объекту `TelegramHelper $telegramHelper`

Далее отправляем сообщения:

`$this->telegram->event('Error', 'Текст уведомления');`

В попытке найти способ использовать Уведомления через телеграмм независимо (например пакет не установлен) был реализован способ отправки через Sumfony eventDispatcher,

С помощью autowire получаем доступ к объекту `EventDispatcherInterface $eventDispatcher`

Далее отправляем сообщения:

```
$event = new TelegramEvent("Error", 'TEST TEST TEST');
$this->eventDispatcher->dispatch($event, TelegramEvent::class);
```

Метод рабочий, но требует `use JustCommunication\TelegramBundle\Event\TelegramEvent;`