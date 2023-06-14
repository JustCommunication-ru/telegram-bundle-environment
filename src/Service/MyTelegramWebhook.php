<?php

namespace App\Service;

use JustCommunication\TelegramBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Service\SmsAeroHelper;
use JustCommunication\TelegramBundle\Service\TelegramHelper;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use JustCommunication\TelegramBundle\Service\TelegramWebhook;

/**
 * Контролер по сути нужен только для webhook-а
 * Вся логика общения с пользователем прям тут, работа с телеграм через сервис TelegramHelper
 */
//#[AsDecorator(decorates: \JustCommunication\TelegramBundle\Service\TelegramWebhook::class)]
class MyTelegramWebhook extends TelegramWebhook
{

    public function __construct(TelegramHelper $telegram, ParameterBagInterface $services_yaml_params, SmsAeroHelper $smsAeroHelper)
    {
        parent::__construct($telegram, $services_yaml_params, $smsAeroHelper);
    }

    /**
     * Реакция на сообщение от пользователя не содержащее команды
     * @param $mess
     * @return string|array
     */
    public function justTextResponse($mess): string|array
    {
        if ($mess=="Отказаться"){
            return [
                'text'=>'Вы сможете пройти идентификаицю в удобное для Вас время позже.',
                'reply_markup'=>json_encode(['remove_keyboard'=>true])
            ];
        }else {
            // Если не команду прислали тогда корчим из себя клоуна
            $ans_arr = array(
                //'Ничего не понятно, но очень интересно!',
                //'Ты сейчас с кем разговариваешь?',
                //$this->user['first_name'] . ', что тебе надо?',
                //'Выражайся точнее!',
                //'Набери /start, я тебе расскажу, что ты можешь сделать, пока я не сказал куда тебе идти.',
                //'Я тебя не понял'
                'Пошли все нахуй. вот.'
            );
            return $ans_arr[rand(0, count($ans_arr) - 1)];
        }
    }

    public function heLpUserCommand($params = []){
        return 'ченадо?';
    }
}