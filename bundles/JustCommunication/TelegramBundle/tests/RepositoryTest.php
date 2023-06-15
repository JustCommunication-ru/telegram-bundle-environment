<?php

namespace JustCommunication\TelegramBundle\Tests\Unit;

use JustCommunication\FuncBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Entity\TelegramUserEvent;
use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserEventRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class RepositoryTest extends KernelTestCase
{

    public TelegramEventRepository $telegramEventRepository;
    public TelegramUserRepository $telegramUserRepository;
    public TelegramUserEventRepository $telegramUserEventRepository;

    function setUp():void{
        self::bootKernel();
        $container = static::getContainer();
        $this->telegramEventRepository = $container->get(TelegramEventRepository::class);
        $this->telegramUserRepository = $container->get(TelegramUserRepository::class);
        $this->telegramUserEventRepository = $container->get(TelegramUserEventRepository::class);
    }

    public function testTelegramEventsExist(){
        $events = $this->telegramEventRepository->getEvents(true);
        //var_dump($events);
        //$this->assertTrue(true);
        $name = FuncHelper::baseClassName($this->telegramEventRepository->getClassName());
        $this->assertGreaterThan(1, count($events), "Warning! No rows of ".$name);
    }
    public function testTelegramUsersExist(){
        $users = $this->telegramUserRepository->getUsers(true);
        //$this->assertTrue(true);
        $name = FuncHelper::baseClassName($this->telegramEventRepository->getClassName());
        $this->assertGreaterThan(1, count($users), "Warning! No rows of ".$name);
    }

    public function testTelegramUserEventErrorForAdminExist(){
        $user_chat_id = $_ENV['JC_TELEGRAM_ADMIN_CHAT_ID'];
        $event_name = 'Error';
        $userevent = $this->telegramUserEventRepository->getUserEvent($user_chat_id, $event_name);
        $this->assertEquals(TelegramUserEvent::class, $userevent?get_class($userevent):'null', 'Warning! Not found TelegramUserEvent for user='.$user_chat_id.' and event='.$event_name);
    }

    public function testTelegramUserEventsForAdminMoreThanOne(){
        $user_chat_id = $_ENV['JC_TELEGRAM_ADMIN_CHAT_ID'];
        $userevents = $this->telegramUserEventRepository->getUserEvents($user_chat_id);
        $this->assertGreaterThan(1, count($userevents));
    }

    public function testTelegramUserEventsErrorAdminTurnOn(){
        $user_chat_id = $_ENV['JC_TELEGRAM_ADMIN_CHAT_ID'];
        $event_name = 'Error';
        $active = 1;
        $res = $this->telegramUserEventRepository->setActive($user_chat_id, $event_name, $active);
        $this->assertTrue(true);// не понятно что проверять
    }

    public function testTelegramGetEventUserIds(){
        $event_name = 'Error';
        $ids = $this->telegramUserEventRepository->getEventUserIds($event_name);
        //var_dump($res);
        $this->assertGreaterThan(1, count($ids));
    }
}