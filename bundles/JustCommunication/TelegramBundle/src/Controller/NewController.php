<?php

namespace JustCommunication\TelegramBundle\Controller;

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
    public function index(ParameterBagInterface $bag){

        $arr = $bag->get('arr');
        //var_dump($arr);
        //return new Response('<h1>hello Telega</h1>');
        return $this->render('@Telegram/new/index.html.twig', ['raw'=>'<h1>hello Telega'.$this->my_param.'</h1>', 'APP_NAME'=>'sdfasdfs']);
    }
}