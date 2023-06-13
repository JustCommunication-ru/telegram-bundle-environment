<?php

namespace JustCommunication\TelegramBundle\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * 2021-11-19
 * Библиотечка для отправки смс по api сервиса SmsAero
 * Требует наличия сервисов $redisHelper, $telegramHelper
 * Кеш переведен на кросс-платформенную редиску к которой будут обращаться все проекты семейства marketplace
 *
 * Специаильный массив в редиске фиксирует только превышение лимита (то есть, пока лимит не превышен, записей в нем нет)
 * Нахера он тогда вообще нужен если мы превышение проверяем по БД???
 * $redis->hgetall(SmsAeroHelper::REDIS_KEY);
 */
class SmsAeroHelper
{
    public array $config;
    private $db = null;
    public $debug = array();
    public $redis = null;
    public $telegram = null;

    const REDIS_KEY = 'sms_send_over';
    const RESULT_CODE_SUCCESS = 1;
    const RESULT_CODE_FAIL = 6;
    const RESULT_CODE_ERROR = 8;
    const RESULT_CODE_STUB_TURN_ON = 5;
    const RESULT_CODE_WRONG_PHONE_NUMBER = 3;
    const RESULT_CODE_LIMIT_OVER = 9;

    //, CacheHelper $cacheHelper
    public function __construct(ParameterBagInterface $params, Connection $connection, RedisHelper $redisHelper, TelegramHelper $telegramHelper, LoggerInterface $logger)
    {
        $this->config = $params->get("smsaero");
        $this->db = $connection;
        //$this->cache = $cacheHelper->getCache();
        $this->redis = $redisHelper->getClient();
        $this->telegram = $telegramHelper;
        $this->logger = $logger;
    }

    public function resend($id){
        //send($phone, $text, $action='default', $code='', $try=1);
    }

    /**
     * Выборка количество отправленных смс с одного ip за последние сутки, для определения превышения лимита
     * @param $ip
     * @return false|mixed
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSendedMessageCountByIp($ip){
        // result_code 1-success_send, 5-stub, 6-error, 9-ban
        $statement = $this->db->prepare('SELECT COUNT(*) as cnt FROM log_sms WHERE datein>NOW()-interval 1 DAY AND ip="'.$ip.'" AND result_code IN (1,5)');
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();
        return $row['cnt']??false;
    }


    /**
     * Выборка статистики отправленных смс с группировкой по ip за прошедшие сутки
     * @return array[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSendedMessageDayStat(){
        // result_code 1-success_send, 5-stub, 6-error, 9-ban
        $statement = $this->db->prepare('SELECT ip, GROUP_CONCAT(distinct phone SEPARATOR ",") AS phones, SUM(if(result_code=1 or result_code=5, 1, 0)) AS count_sended, SUM(if(result_code=9, 1, 0)) AS count_banned FROM log_sms WHERE datein>NOW()-interval 1 DAY GROUP BY ip ORDER BY count_banned, count_sended');
        $result = $statement->executeQuery();
        $rows = $result->fetchAllAssociative();
        return $rows;
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
        $count_by_ip = $this->getSendedMessageCountByIp($ip);
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
                $this->telegram->event('Smsover', 'Превышение лимита отправки смс ('.$this->config['day_limit_per_ip'].') с одного ip ('.$ip.')');
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
                'id_users'=>$id_users,
                'phone'=>$phone,
                'action'=>'smsaero:'.$action,
                'code' =>$code,
                'mess'=>$text,
                'try'=>$try,
                'ip'=> $ip,
                'sended'=>$sended?1:0,
                'result'=>$result,
                'result_code'=>$result_code
            );

            $affected_rows = $this->db->executeStatement(
                'INSERT INTO log_sms SET id_users=?, datein=now(), phone=?, action=?, code=?, mess=?, try=?, ip=?, sended=?, result=?, result_code=?',
                array_values($values) //,$flatTypes
            );
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

