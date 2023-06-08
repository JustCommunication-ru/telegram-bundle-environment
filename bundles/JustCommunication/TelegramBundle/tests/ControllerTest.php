<?php

namespace JustCommunication\TelegramBundle\Tests\Unit;

//ini_set('error_reporting', E_ALL);
//ini_set("display_errors", 1); // для development =1 (ниже)


//use JustCommunication\TelegramBundle\Tests\App\TestingKernel;
use PHPUnit\Framework\TestCase;
//use Psr\Log\LoggerInterface;
//use Symfony\Component\HttpKernel\HttpKernelBrowser;


class ControllerTest extends TestCase
{

    function setUp():void{
        //$kernel = new TestingKernel();
    }

/*
    public static function createClient()
    {
        $kernel = new TestingKernel();
        return new HttpKernelBrowser($kernel);
    }
    public function testSomeTest(){
        $client = static::createClient();
        $response = $client->request("GET", "/telega");
        //dd($response);
        $this->assertTrue(true);
    }
    */
    public function testStart(){

        $this->assertTrue(true);
    }
}