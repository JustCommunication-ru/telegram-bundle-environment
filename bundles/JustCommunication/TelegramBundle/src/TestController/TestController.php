<?php

namespace JustCommunication\TelegramBundle\TestController;

use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use JustCommunication\TelegramBundle\Trait\CacheTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController{


    #[Route('/test/getevents', name:'get_event')]
    public function get_events(TelegramEventRepository $eventRepository){

        $x = $eventRepository->getEvents(true);
//JC_TELEGRAM_ADMIN_CHAT_ID

        return new JsonResponse(json_encode(["ans"=>"aaa"], JSON_UNESCAPED_UNICODE));
    }


    /*
    function number1(#[Autowire(service: 'monolog.logger.request')]
                    CacheInterface $cache){
        return $cache;
    }
    */
    /*
    function number(#[Autowire(expression: 'service("JustCommunication\\TelegramBundle\\Service\\CacheHelper")')]
                    CacheInterface $cache){
        return $cache;
    }
    */

}