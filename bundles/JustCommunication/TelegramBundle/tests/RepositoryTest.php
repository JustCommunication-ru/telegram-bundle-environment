<?php

namespace JustCommunication\TelegramBundle\Tests\Unit;

//ini_set('error_reporting', E_ALL);
//ini_set("display_errors", 1); // для development =1 (ниже)


use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Tests\App\TestingKernel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Contracts\Service\Attribute\Required;


class RepositoryTest extends KernelTestCase
{

    #[Required]
    public TelegramEventRepository $telegramEventRepository;

    function setUp():void{

    }

    public function testGetEvents(){

        self::bootKernel();
        $container = static::getContainer();

        $telegramEventRepository = $container->get(TelegramEventRepository::class);
        $telegramEventRepository->getEvents(true);

        //dd($response);
        $this->assertTrue(true);
    }
}