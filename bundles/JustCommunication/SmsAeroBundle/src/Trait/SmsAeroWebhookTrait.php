<?php

namespace JustCommunication\SmsAeroBundle\Trait;

use JustCommunication\SmsAeroBundle\Service\SmsAeroHelper;
use Symfony\Contracts\Service\Attribute\Required;

trait SmsAeroWebhookTrait
{
    #[Required]
    public SmsAeroHelper $smsAeroHelper;

    /**
     * Конфиги смс
     * @param $params
     * @return string
     */
    public function smsConfSuperuserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: '."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        // берем местные конфиги напрямую
        $smsaero_config = $this->services_yaml_params->get('smsaero');
        $text = '*SmsAero конфигурация:*'."\r\n".
            '*Состояние*: '.($smsaero_config['stub']?'режим заглушки':'включён')."\r\n".
            '*Логин*: '.$smsaero_config['login']."\r\n".
            '*Url*: '.$smsaero_config['url']."\r\n".
            '*Логирование*: '.($smsaero_config['log_sms']?'включено':'выключено')."\r\n".
            '*Лимит сообщений с одного IP*: '.$smsaero_config["day_limit_per_ip"]."\r\n".
            "\r\n".
            'Посмотреть статистику отправки смс /smsStat';
        return $text;
    }


    /**
     * Статистика отправки смс
     * @param $params
     * @return string
     */
    public function smsStatSuperuserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: '."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $rows = $this->smsAeroHelper->getSendedMessageDayStat();

        $text = 'SmsAero статистика за сутки:'."\r\n";
        if (count($rows)){
            $text .= '```'."\r\n";
                //$text .= '`ip.address sended/tries phones...`'."\r\n";
            $text .= $this->listColFormat('act', 5).'|'.$this->listColFormat('ip.address', 15).'|'.$this->listColFormat('sn/tr', 5).'|'.$this->listColFormat('phones', 11)."\r\n";
            foreach($rows as $row) {
                $text .= $this->listColFormat($row['action'], 5).'|'.$this->listColFormat($row['ip'], 15).'|'.$this->listColFormat($row['count_sended'].'/'.($row['count_sended']+$row['count_banned']), 5).'|'.$this->listColFormat($row['phones'], 11)."\r\n";

                //$text .= '`' . $row['ip'].str_repeat(' ',15-strlen($row['ip'])).' '.$row['count_sended'].'/'.($row['count_sended']+$row['count_banned']).' '.$row['phones'].'`'."\r\n";
            }
            $text .= '```'."\r\n";


        }else{

            $text.='нет отправок.';
        }

        return $text;
    }


    /**
     * отправка смс
     * @param $params
     * @return string
     * @throws \Doctrine\DBAL\Exception
     */
    public function smsSendSuperuserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: '."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        // по своему распарсим команду. нам нужно конкретно два параметра
        if (strpos($this->mess, " ")>0){
            $arr = explode(" ",str_replace("/", "", $this->mess), 3);
        }else{
            $params = array();
        }
        $phone = isset($arr[1])?str_replace('+', '', $arr[1]):"";
        $sms_mess = $arr[2]??"";

        if ($phone=="" || $sms_mess==""){
            $text = 'Для команды необходимо указать два параметра: телефон и сообщение, формат такой: "/smsSend +79009990909 все остальное это текст сообщения"';
        }else {
            if (strlen($phone) == 11) {
                $sended =  $this->smsAeroHelper->send($phone, $sms_mess, 'send');
                $text =  $this->smsAeroHelper->getFailMessage();
            } else {
                $text = 'Не корректный формат номера телефона, должно быть 11 цифр';
            }
        }
        return $text;
    }

}