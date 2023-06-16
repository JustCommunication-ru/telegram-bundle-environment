<?php

namespace JustCommunication\TelegramBundle\EventSubscriber;

use JustCommunication\TelegramBundle\Event\TelegramEvent;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
autowire: EventDispatcherInterface $eventDispatcher
$event = new TelegramEvent("Error", 'TEST TEST TEST');
$this->eventDispatcher->dispatch($event, TelegramEvent::class);
 */
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
            TelegramEvent::class=>'onTelegramEvent',
        ];
    }

    public function onTelegramEvent(TelegramEvent $event){
        $this->telegram->event($event->getEventName(), $event->getMess());
    }

}