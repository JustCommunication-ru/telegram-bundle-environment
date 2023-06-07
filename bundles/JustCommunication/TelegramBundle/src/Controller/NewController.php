<?php

namespace JustCommunication\TelegramBundle\Controller;

use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use JustCommunication\TelegramBundle\TelegramBundle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewController extends AbstractController{

    public $my_param;
    public function __construct($my_param='nothing')
    {
        $this->my_param=$my_param;
    }

    #[Route('/telega')]
    public function index(ParameterBagInterface $bag, TelegramHelper $telegramHelper, TelegramEventRepository $eventRepository){

        //$arr = $bag->get('justcommunication.telegram.config');
        //var_dump($arr);

        /*
        echo "\xE2\x80\xBC ".' i che delat1?';
        echo "\r\n";
        echo $telegramHelper->emoji('\xE2\x80\xBC '.' i che delat2?');
*/
        //$telegramHelper->sendMessage('537830154', '\xE2\x80\xBC i che delat или не делать?');

        //$x = $telegramHelper->getEvents(true);



        //$x = $eventRepository->findAll();

        $x = $eventRepository->getEvents();

        var_dump($x);


        //return new Response('<h1>hello Telega</h1>');
        return $this->render('@Telegram/new/index.html.twig', ['raw'=>'<h1>hello Telega'.$this->my_param.'</h1>', 'APP_NAME'=>'sdfasdfs']);
    }
}