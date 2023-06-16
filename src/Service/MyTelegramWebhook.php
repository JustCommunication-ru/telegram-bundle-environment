<?php

namespace App\Service;

use JustCommunication\SmsAeroBundle\Trait\SmsAeroWebhookTrait;
use JustCommunication\TelegramBundle\Service\TelegramWebhook;

class MyTelegramWebhook extends TelegramWebhook
{
    use SmsAeroWebhookTrait;

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

    public function hellUserCommand($params = []){
        return 'ченадо?';
    }
}