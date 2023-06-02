<?php

namespace JustCommunication\TelegramBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewController extends AbstractController{
    #[Route('/telega')]
    public function index(){

        return new Response('<h1>hello Telega</h1>');
    }
}