<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class IndexController extends AbstractController
{

    #[Route('/')]
    public function index(){

        return new Response('hello world on '. __FUNCTION__.' '. rand(1111,9999));
    }

    #[Route('/page')]
    public function page():Response
    {
        return new Response('hello page');
    }

    #[Route('/api/delivery', requirements: [], name: "api_delivery", priority: 100)]
    public function api_delivery(){

        return new Response('hello tel');
    }

}