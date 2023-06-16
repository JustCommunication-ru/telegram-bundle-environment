<?php

namespace JustCommunication\SmsAeroBundle\Service;

use JustCommunication\FuncBundle\Service\FuncHelper;
use JustCommunication\SmsAeroBundle\Event\SmsAeroEvent;
use JustCommunication\SmsAeroBundle\Repository\LogSmsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Библиотечка для отправки смс по api сервиса SmsAero
 * Требует наличия "сервиса" $redisHelper
 * Кеш переведен на кросс-платформенную редиску
 *
 * Специаильный массив в редиске фиксирует только превышение лимита (то есть, пока лимит не превышен, записей в нем нет)
 * Нахера он тогда вообще нужен если мы превышение проверяем по БД???
 * $redis->hgetall(SmsAeroHelper::REDIS_KEY);
 */
class SmsAeroHelper
{
    public array $config;
    public $debug = array();
    public $redis;
    public LogSmsRepository $logSmsRepository;
    public EventDispatcherInterface $eventDispatcher;

    const REDIS_KEY = 'sms_send_over';
    const RESULT_CODE_SUCCESS = 1;
    const RESULT_CODE_FAIL = 6;
    const RESULT_CODE_ERROR = 8;
    const RESULT_CODE_STUB_TURN_ON = 5;
    const RESULT_CODE_WRONG_PHONE_NUMBER = 3;
    const RESULT_CODE_LIMIT_OVER = 9;


    public function __construct(ParameterBagInterface $params,
                                RedisHelper $redisHelper, LoggerInterface $logger,
                                LogSmsRepository $logSmsRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->config = $params->get("smsaero");
        $this->redis = $redisHelper->getClient();
        $this->logger = $logger;
        $this->logSmsRepository = $logSmsRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function resend($id){
        //send($phone, $text, $action='default', $code='', $try=1);
    }

    public function getSendedMessageDayStat(){
        return $this->logSmsRepository->getSendedMessageDayStat();
    }

    /**
     * Отправка сообщений
     * @param $phone - полный номер телефона (может начинаться с +7... , 7..., 8...)
     * @param $text - текст сообщения (одна смс - 70кириллицей)
     * @param string $action - действие (для сбора статистики)
     * @param string $code - если смс для передачи кода, то указать дополнительно сюда (для статистики)
     * @param int $id_users - пользователь которому предназначается смс
     * @param int $try - попытка (если не первая)
     * @param string $ip - ip для того, кому отправляется сообщение
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function send($phone, $text, $action='default', $code='', int $id_users=0, int $try=1, string $ip=''){


        // Проверяем телефон на корректность, доп условие, надо чтобы он потом в базу в поле на 12 символов поместился
        if ($ip=='') {
            $ip = FuncHelper::GetIP();
        }


        // $this->config['day_limit_per_ip']
        //$reg_limit_allow = Celib::getInstance('Module:Users')->cache_counter('phone_reg_'.$this->auth4->id, $tries, $time);

        // заглушки тоже считает
        $count_by_ip = $this->logSmsRepository->getSendedMessageCountByIp($ip);
        // 1) проверка превышения лимита отправки с ip
        if ($count_by_ip<$this->config['day_limit_per_ip']) {
            $this->redis->hdel(self::REDIS_KEY, $ip); // Если нет превышения будем перманенто чистить запись

            // Проверка на корректность телефонного номера
            if (!FuncHelper::isPhone($phone)){
                $sended = false;
                $result = 'wrong phone value format: ('.$phone.')';
                $phone = substr($phone, 0, 12); // режем чтобы сохранить в базу бе проблем
                $result_code = self::RESULT_CODE_WRONG_PHONE_NUMBER;
                $this->debug = array(
                    'post' => '-none-',
                    'url' => '-none-',
                    'result' => $result,
                    'result_code' => $result_code
                );
            // проверка на режим заглушки
            }elseif ($this->config['stub']) {
                $sended = false;
                $result = 'stub';
                $result_code = self::RESULT_CODE_STUB_TURN_ON;
                $this->debug = array(
                    'post' => '-none-',
                    'url' => '-none-',
                    'result' => 'Stub success.',
                    'result_code' => $result_code
                );

            // если всё хорошо пробуем отправить
            } else {
                $ch = curl_init();
                $send_link = $this->config['url'] . 'sms/send/';
                curl_setopt($ch, CURLOPT_URL, $send_link);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->config['login'] . ":" . $this->config['password']);
                curl_setopt($ch, CURLOPT_POST, true);
                $post_arr = array(
                    //'user='.$this->config['login'],
                    //'password='.$this->config['password'],
                    'sign=' . $this->config['sign'],
                    'number=' . $phone,
                    'text=' . $text,
                    'channel=' . 'DIRECT',
                    'answer=json',
                );
                curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $post_arr));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // возвращаемое значение
                $result = curl_exec($ch);

                $curl_getinfo = curl_getinfo($ch);
                $curl_getinfo['curl_errno']=curl_errno($ch);
                $curl_getinfo['curl_error']=curl_error($ch);

                /*echo curl_error($ch);
                echo '<br>';
                print_r($post_arr);
                print_r(curl_getinfo($ch));
                print_r($result);*/
                curl_close($ch);

                $arr = json_decode($result, true);

                if ($curl_getinfo['http_code']==200 && $curl_getinfo['curl_errno']==0) {
                    $result_code = isset($arr['success']) && $arr['success'] === true ? self::RESULT_CODE_SUCCESS : self::RESULT_CODE_ERROR;
                }else{
                    $result_code = self::RESULT_CODE_FAIL;
                }


                $this->debug = array(
                    'post' => $post_arr,
                    'url' => $send_link,
                    'result' => $result,
                    'result_code' => $result_code,
                    'curl_error' => $curl_getinfo['curl_errno'].' '.$curl_getinfo['curl_error']
                );

                $sended = isset($arr['success']) && $arr['success'] === true ? true : false;

            }
        }else{

            // Если превышен лимит
            $sended = false;
            $result = 'over the day limit ('.$this->config['day_limit_per_ip'].') for ip ('.$ip.')';
            $result_code = self::RESULT_CODE_LIMIT_OVER;
            $this->debug = array(
                'post' => '-none-',
                'url' => '-none-',
                'result' => $result,
                'result_code' => $result_code
            );

            $current_value = $this->redis->hget(self::REDIS_KEY, $ip); // смотрим только наличие самой записи, по факту переписываем на основе данных из БД

            if (!$current_value){
                $this->redis->hset(self::REDIS_KEY, $ip, json_encode(array('count'=>$count_by_ip+1, 'last_date'=>date('Y-m-d H:i:s'), 'U'=>date('U'))));
                //$this->telegram->event('Smsover', 'Превышение лимита отправки смс ('.$this->config['day_limit_per_ip'].') с одного ip ('.$ip.')');

                $event = new SmsAeroEvent("Smsover", 'Превышение лимита отправки смс ('.$this->config['day_limit_per_ip'].') с одного ip ('.$ip.')');
                $this->eventDispatcher->dispatch($event, SmsAeroEvent::class);
            }else{
                //$this->redis->hincrby(self::REDIS_KEY,$ip, 1); // сюда кстати можно тоже не инкремент а сет делать
                $this->redis->hset(self::REDIS_KEY,$ip, json_encode(array('count'=>1, 'last_date'=>date('Y-m-d H:i:s'), 'U'=>date('U'))));
            }
            //$redis->hdel($key,array_keys($pre_list)[$rd]);
            //$redis->hincrby($key,$ip, 1);
            //$list = $redis->hgetall($key);
            //var_dump($list);
        }

        // Если включено логирование отправленных смс. Должна быть табличка log_sms (LogSms Entity)
        if ($this->config['log_sms']){

            $values = array(
                'id_user'=>$id_users,
                'phone'=>$phone,
                'action'=>$action,
                'code' =>$code,
                'mess'=>$text,
                'try'=>$try,
                'ip'=> $ip,
                'sended'=>$sended?1:0,
                'result'=>$result,
                'result_code'=>$result_code
            );
            $this->logSmsRepository->newLog($values);
        }

        //return $sended || $this->config['stub'];
        return $sended || $result_code==self::RESULT_CODE_STUB_TURN_ON;
    }

    public function getDebug(){
        return $this->debug;
    }

    /**
     * Описание результата последней отправки
     * @return string
     */
    public function getFailMessage(){
        return $this->debug['result_code']?
            match ($this->debug['result_code']) {
                self::RESULT_CODE_SUCCESS => 'Успешно отправлено',
                self::RESULT_CODE_ERROR => 'Ошибка парсинга ответа от смс-сервиса',
                self::RESULT_CODE_FAIL => 'Ошибка выполнения запроса на смс-сервис ('.($this->debug['curl_error']??'').')',
                self::RESULT_CODE_STUB_TURN_ON => 'Отправка сообщений отключена. Сообщение сохранено.',
                self::RESULT_CODE_WRONG_PHONE_NUMBER => 'Некорректный номер телефона',
                self::RESULT_CODE_LIMIT_OVER => 'Исчерпан лимит отправки сообщений с одного IP ('.$this->config['day_limit_per_ip'].')',
            }:'Неизвестная ошибка';
    }


}

