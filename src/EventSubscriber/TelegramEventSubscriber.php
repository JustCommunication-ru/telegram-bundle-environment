<?php

namespace App\EventSubscriber;

use JustCommunication\SmsAeroBundle\Event\SmsAeroEvent;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TelegramEventSubscriber implements EventSubscriberInterface
{

    private TelegramHelper $telegram;

    public function __construct(TelegramHelper $telegram){
        $this->telegram = $telegram;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // чтобы не плодить обработчиков (и не отслеживать потом их на местах), все вызываются по имени класса события
            SmsAeroEvent::class=>'onSmsAeroEvent',
        ];
    }

    public function onSmsAeroEvent(SmsAeroEvent $event){
        $this->telegram->event($event->getEventName(), $event->getMess());
    }

}