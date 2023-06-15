<?php

namespace JustCommunication\TelegramBundle\Service;

use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Repository\TelegramMessageRepository;
use JustCommunication\TelegramBundle\Repository\TelegramSaveRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserEventRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserRepository;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Сервис для работы с телеграм-уведомлениями, ботом и всем что с этим связано.
 *
 * Если в этом блоке встречаются символы из перечисленных: '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'  — их нужно экранировать, добавлять перед ними обратный слэш \
 * some text /somepath Если перед слешем стоит пробел, то интерпритируются как команда, как исправить? хз используем два слеша или слитно при чем иногда слитно через пробел работает в других случаях нет
 * dgsdfg *on*
 * https://core.telegram.org/bots/api - листать вниз до Available methods
 * emoji(Bytes (UTF-8)): https://apps.timwhitlock.info/emoji/tables/unicode
 */
class TelegramHelper
{
    public $db;
    public $config;
    public $cache;
    public $logger;
    public TelegramEventRepository $telegramEventRepository;
    public TelegramSaveRepository $telegramSaveRepository;
    public TelegramUserRepository $telegramUserRepository;
    public TelegramUserEventRepository $telegramUserEventRepository;
    public TelegramMessageRepository $telegramMessageRepository;
    public $em;

    //public $add_reply_emoji="\xE2\x9C\x85 "; //WHITE SMALL SQUARE
    //public $add_reply_emoji="\xE2\x96\xAB "; //WHITE SMALL SQUARE
    public $add_reply_emoji="\xE2\x9A\xA1 "; // 	HIGH VOLTAGE SIGN

    /**
     * Сюда из вызываемого класса можно передать обработчик для вывода информации.
     * @var null
     */
    public $debug_callback = null;

    private UrlGeneratorInterface $router;

    public function __construct(ParameterBagInterface $params,LoggerInterface $logger,
                                TelegramEventRepository $telegramEventRepository,
                                TelegramSaveRepository $telegramSaveRepository,
                                TelegramUserRepository $telegramUserRepository,
                                TelegramUserEventRepository $telegramUserEventRepository,
                                TelegramMessageRepository $telegramMessageRepository,
                                UrlGeneratorInterface $router
    )
    {
        try {
            $this->config = $params->get("justcommunication.telegram.config");
            $this->events = $params->get("justcommunication.telegram.events");

            if (!$this->config['token']){
                throw new \Exception('Telegram token const not set in .env.local [JC_TELEGRAM_TOKEN]', 1);
            }
            $this->logger = $logger;

            $this->telegramEventRepository = $telegramEventRepository;
            $this->telegramSaveRepository = $telegramSaveRepository;
            $this->telegramUserRepository = $telegramUserRepository;
            $this->telegramUserEventRepository = $telegramUserEventRepository;
            $this->telegramMessageRepository = $telegramMessageRepository;
            $this->router = $router;
        }catch (\Exception $exception){
            // Если запускать jc:telegram:install, то пытаются завестить другие command а они требуют хелпера, а хелпер требует конфиги. и сообщения вводят в заблуждение
            // С другой стороны если без установки попытаться воспользоваться функционалом сыпятся непонятные ошибки из-за закоментированного ниже кода
            //throw new \Exception("You need to set config justcommunication.telegram.config, do it manualy or run jc:telegram:install");
        }

    }

    /**
     * Функция вызова отладки (при желании в нее можно передать свою функицю)
     * @param $str
     */
    public function debug($str){
        if (is_callable($this->debug_callback)){
            $foo = $this->debug_callback;
            $foo($str);
        }else{
            echo $str."\r\n";
        }
    }

    /**
     * Регистрация обработчика входящих сообщений
     * @return bool
     */
    public function setWebhook(){
        return $this->query('setWebhook', array('url'=>$this->getWebhookUrl()));
        //return $this->config['webhook'];
    }

    /**
     * Возвращает ссылку на боевой вебхук, даже из dev, по крайней мере на это расчитан
     * Генерация ссылки для вебхука со встроенным хаком для локала, чтобы все проверки проходили корректно и ничего не нарушить на боевом
     * @return string
     */
    public function getWebhookUrl(){
        $webhook_url_path = $this->router->generate('jc_telegram_webhook');
        return ($_ENV['APP_ENV']=='dev'
                        ?$this->config['production_webhook_app_url']
                        :$this->config['app_url'])
            .$webhook_url_path.'?token='.$this->config['token'];
    }


    /**
     * Отключение обработчка входящих сообщений
     * @return array|mixed
     */
    public function delWebhook(){
        return $this->query('deleteWebhook', array());
    }

    /**
     * Если отсутствует привязанный вебхук, то за сообщениями надо лазить самому. с помощью вот этой вот функции
     * @return array|mixed
     */
    public function getUpdates(){
        return $this->query('getUpdates', array());
    }

    /**
     * @return bool
     */
    public function  getWebhookInfo(){
        return $this->query('getWebhookInfo');
    }

    private array $last_ans = array();

    public function get_last_result(): array{
        return $this->last_ans;
    }

    /**
     * @return string
     */
    public function get_query_status(): string{
        $result = $this->last_ans;
        if (isset($result['ok']) && $result['ok']==1){
            $res = 'ok';
        }else{
            $res ='error';
            if (isset($result['description'])){
                $res .= "\r\n". 'Telegram return error: '.$result['description'];
            }
            if (isset($result['curl_errno'])){
                $res .= "\r\n". 'Telegram curl error: '.$result['curl_errno'].' '.$result['curl_error'];
            }
        }
        return $res;
    }

    /**
     * Индекс минимального значения в массиве? почему так?
     * @param $arg
     * @return false|int|string,
     */
    private function index_of_min($arg){
        $min =999999;
        $index = false;
        foreach ($arg as $i=>$v){
            if ($v!==false && $v<$min){
                $min = $v;
                $index = $i;
            }
        }
        return $index;
    }

    /**
     * Проверка маркдауна
     * @param $str
     * @param bool $fix
     * @param bool $fix_postfix
     * @return string|string[]
     */
    private function markdown_checker($str, $fix=false, $fix_postfix=false){
        $origin_str = $str;
        $res =  [];
        $shift = 0;

        // 2023-02-09 флаг для найденного хотя бы одного незакрытого тэга
        $fonudUnclosed = false;

        while($str!=''){
            $arr_left = ['[','_','*','```','`']; // ` именно в таком порядке приоритета
            $arr_right = [']','_','*','```','`'];
            $pos =[];
            foreach ($arr_left as $tag){
                $pos[] = mb_strpos($str, $tag);
            }

            $index = $this->index_of_min($pos);

            if ($index!==false) {
                //нашли тег
                $left_char = $arr_left[$index];
                $right_char = $arr_right[$index];
                $pos_left = $pos[$index];

                //----------------------------
                $pos_right = mb_strpos($str, $right_char, $pos_left+1);
                if ($pos_right!==false){
                    // есть до
                    if ($pos_left>0) {
                        $res[] = [mb_substr($str, 0, $pos_left),'str'];
                    }
                    //тело экранирования
                    $res[] = [mb_substr($str, $pos_left, $pos_right-$pos_left+strlen($right_char)), $left_char.$right_char];
                    // есть конец или нет
                    if ($pos_right+strlen($right_char)<mb_strlen($str)) {
                        $shift+=$pos_right+strlen($right_char);
                        $str = mb_substr($str, $pos_right+strlen($right_char));
                    }else{
                        $str = '';
                    }
                }else{

                    // не закрытый тег
                    // 2023-02-09 запомнить, если найден незакрытый тег
                    $fonudUnclosed = true;
                    // дальше нет экранирования
                    //$res[$str] = 'str_broken';
                    if ($fix) {
                        $res[] =[$str . $right_char, $left_char . $right_char];
                        $str = '';
                    }else{
                        $rest_cnt =mb_strlen($str)-$pos_left;

                        //echo ($str);die();
                        return array('result'=>'error', 'mess'=>'unclosed entitity "'.$left_char.'" starts on '.($shift+$pos_left).' ('.mb_substr($str, $pos_left, min(20, $rest_cnt)).')');
                    }
                }
                //----------------------------
            }else{
                // дальше нет тэгов
                $res[] = [$str,'str'];
                $str = '';
            }
        }

        if ($fix) {
            $appendWarn = '';
            if($fonudUnclosed){

                // 2023-02-09 если незакрытые тэги встречались - честно в этом признаться
                $appendWarn = ($fix_postfix?"\r\n".'`Сообщение содержало ошибку разметки, возможно некорректное отображение`':'');
            }
            /*
             * отладка
dd([$origin_str, array_map(function($item) {
    return $item[0];
},$res)]);
            */


            return implode('', array_map(function($item) {return $item[0];},$res)).$appendWarn;
        }else{
            return array('result'=>'success', 'mess'=>'success');
        }

    }



    /**
     * @return array
     */
    public function getDebug(): array{
        return $this->debug;
    }

    /**
     * @return int
     */
    public function getAdminChatId(){
        return $this->config['admin_chat_id'];
    }


    //------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------





    /**
     * Отправляет сообщение/редактирует ранее сохраненное, сохраняет полученное при необходимости
     * @param int $chat_id
     * @param string $mess
     * @param array $params
     * @return mixed
     */
    //public function  sendMessage(int $chat_id, string $text, $entities= []): array{
    public function  sendMessage(int $chat_id, string $mess, $params=[]): array{
        $teleSave = null;
        if (isset($params['load_id'])){
            $teleSave = $this->telegramSaveRepository->findOneBy(['ident'=>$params['load_id'], 'userChatId'=>$chat_id]);
        }
        $data = ['text'=>$mess];

        if ($teleSave && isset($params['add_text']) && $params['add_text']!=''){
            // Изменяем начальное сообщение

            $teleSave = $this->telegramSaveRepository->updateMess($teleSave, $teleSave->getMess()."\r\n".$this->add_reply_emoji.$params['add_text']);

            $pre_ans = $this->sendMess($chat_id, [
                'message_id'=>$teleSave->getMessageId(),
                'text'=>$teleSave->getMess()
            ], 'editMessageText');



            // Делаем новое сообщение ответом на предыдущее
            $data['reply_to_message_id']=$teleSave->getMessageId();
        }

        $ans = $this->sendMess($chat_id, $data);

        if (isset($ans['ok']) && $ans['ok']){
            // Если была инструкция сохранить сообщение по идентом
            if (isset($params['save_id'])){
                // Если надо сохраняем каждое отправленное сообщение отдельно, пусть орм подавится.
                $this->telegramSaveRepository->newSave($params['save_id'], $ans['result']['chat']['id'], $ans['result']['message_id'], $mess);


                $this->logger->debug('TelegramSave by ident: '.$params['save_id'].' for: '.$chat_id);
            }
        }else{
            $ans['ok'] = false;
        }

        return $ans;
    }

    /**
     * 2022-08-19 Новый метод, непосредственная отправка, на вход ждет не сообщение, а структуру
     * его бы сделать private... наверно
     * @param int $chat_id
     * @param array $data
     * @param string $method
     * @return array
     */
    public function  sendMess(int $chat_id, array $data, string $method='sendMessage'): array{
        $data['text'] = $this->emoji($data['text']??''); // ищем эмоджи и правильно их конвертим
        return $chat_id>0
            ?$this->query($method, array_merge(
                array(
                    'chat_id'=>$chat_id,
                    'text'=>'',
                    'parse_mode'=>'Markdown'
                ), $data))
            :array('curl_error'=>'no chat_id', 'ok'=>false);
    }


    /**
     * Осуществить рассылку по эвенту, сообщение с переносом строк
     * @param string $event
     * @param string $mess
     * @return array
     */
    public function event(string $event, string $mess, $params=[]): array{

//        $mess = '``` '.$event.'```'."\r\n".$mess;
//        $mess .= "\r\n".'/remove'.$event.' - отписаться от рассылки';

        $mess = str_replace(array('<br>'), array("\r\n"), $mess);
        $event = mb_convert_case(addslashes($event), MB_CASE_TITLE); // зачем addslashes? можно же порезать всё лишнее

        // Будем ронять скрипт если попытались отправить несуществующий евент, а у нас strict mode
        if (!array_key_exists($event, $this->getEvents()) && $this->config['wrong_event_exception']) {
            throw new \Exception('Event "'.$event.'" not found, try app:telegram --init for repair');
        }

        //wrong_event_exception

        // почему выборка юзверей не через функцию а прям тут?
        // повышаем живучесть механизма, если слетела базка, отправляем весточку админу!
        try {
            $subscriber_ids = $this->telegramUserEventRepository->getEventUserIds($event);
        }catch (\Exception $e){
            $mess = '*Ошибка рассылки сообщений!*'."\r\n".'Запрос подписчиков на событие '.$event.' провалился с исключением: ``` '.$e->getMessage().'```'."\r\n\r\n".$mess;
            $subscriber_ids = [$this->getAdminChatId()];
        }

        $answers = array();
        $ok = true;
        if (count($subscriber_ids)){

            // В случае ответа на ранее сохраненное сообщение необходимо его найти в базе
            $teleSavesMap = [];
            if (isset($params['load_id'])){
                $load_idents_arr=[];
                if (strpos($params['load_id'],',')){
                    $load_idents_arr = explode(',', $params['load_id']);
                }else{
                    $load_idents_arr[] =$params['load_id'];
                }
                foreach($load_idents_arr as $load_ident) {
                    $teleSaves = $this->telegramSaveRepository->findBy(['ident' => $load_ident]);
                    if (count($teleSaves)) {
                        foreach ($teleSaves as $teleSave) {
                            $teleSavesMap[$teleSave->getUserChatId()] = $teleSave;
                        }
                        break; // Используем load_id иденты только один в порядке приоритетности из списка!!!
                    }
                }
            }

            // Выполняем поочередную отправку телеграм сообщений всем пользователям пока не наткнемся на ошибку
            foreach($subscriber_ids as $user_id){
                // Отправляем всем получателям, если не произойдет никакой ошибки
                // в 99,99% если произошла ошибка то либо сервер мертв либо ошибка в тексте и остальные не отправятся тоже
                if ($ok){
                    $data = ['text'=>$mess];

                    // В случае ответа на сообщение редактируем его в чате!

                    if (isset($teleSavesMap[$user_id]) && isset($params['add_text']) && $params['add_text']!=''){
                        // Изменяем начальное сообщение
                        //$teleSavesMap[$user_id]->setMess($teleSavesMap[$user_id]->getMess()."\r\n[__________________________________]\r\n".$params['add_text']);
                        $teleSavesMap[$user_id] = $this->telegramSaveRepository->updateMess($teleSavesMap[$user_id], $teleSavesMap[$user_id]->getMess()."\r\n".$this->add_reply_emoji.$params['add_text']);
                        $pre_ans = $this->sendMess($user_id, array_merge($data, [
                            'message_id'=>$teleSavesMap[$user_id]->getMessageId(),
                            'text'=>$teleSavesMap[$user_id]->getMess()
                        ]), 'editMessageText');

                    }
                    // Не важно изменяли прошлое или нет, на него надо сослаться
                    if (isset($teleSavesMap[$user_id])){
                        // Делаем новое сообщение ответом на предыдущее
                        $data['reply_to_message_id']=$teleSavesMap[$user_id]->getMessageId();
                    }

                    // отправляем само сообщение
                    $ans = $this->sendMess($user_id, $data);

                    if (!isset($ans['ok']) || !$ans['ok']){$ans['ok']=false;
                        $ok = false;
                        /*
                        file_put_contents(__DIR__."/../../var/log/telegram_fails.txt",
                            "\r\n\r\n\r\n".'---------------------- '.date("Y-m-d H:i:S").' ----------------------'."\r\n".
                            'send_to: '.$user_id."\r\n".
                            'text: '.$data['text']."\r\n".
                            "\r\n".
                            'ans: '."\r\n".
                            print_r($this->last_ans, true));
                        */
                    }else{
                        // Если была инструкция сохранить сообщение под идентом
                        if (isset($params['save_id'])){
                            // Если надо сохраняем каждое отправленное сообщение отдельно, пусть орм подавится.
                            $this->telegramSaveRepository->newSave($params['save_id'], $ans['result']['chat']['id'], $ans['result']['message_id'], $mess);

                            $this->logger->debug('TelegramSave by ident: '.$params['save_id'].' for: '.$user_id);
                        }
                    }
                    $answers[]=$ans;
                }
            }
        }
        return $answers;
    }


    /**
     * Отправка запроса API Telegram
     * @param string $method
     * @param array $params
     * @param bool $cut_mode Задача, если не удалось отправить сообщение, то отправляем урезанную версию без форматирования тому же получателю
     * @return array|mixed
     */
    private function query(string $method, $params=array(), $cut_mode=false): array{

        $url = $this->config['url'];
        $url .=$this->config['token'];
        $url .='/'.$method;

        //2023-02-02 text отправляем POST-ом,

        if (isset($params['text'])) {
            $text = $params['text'];
            if ($this->config['length_checker'] && $cut_mode) {
                $char_limit = 1000;
                if (mb_strlen($text) > $char_limit) {
                    $text = mb_substr($text, 0, $char_limit);
                }
                $text = '`это "обрезанная" копия сообщения которое не было доставлено`' . "\r\n" . $text;
            }
            if ($this->config['markdown_checker']) {
                $text = $this->markdown_checker($text, true, true);
            }
            unset($params['text']); // убираем из GET
        }else{
            $text = null;
        }

        if (!empty($params)){
            $url .="?".http_build_query($params);
        }
        /****************/
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&',$post_arr));
        if ($text) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'text=' . $text);
        }
        if ($this->config['proxy']!=''){
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);  // тип прокси
            curl_setopt($ch, CURLOPT_PROXY,  $this->config['proxy']);                 // ip, port прокси
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['proxy_auth']);  // авторизация на прокси
            curl_setopt($ch, CURLOPT_HEADER, false);                // отключение передачи заголовков в запросе
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        // отмена проверки сертификата удаленным сервером
        }
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $info['curl_errno']=(int)curl_errno($ch);
        $info['curl_error']=curl_error($ch);

        curl_close($ch);

        $this->debug = array(
            'url'=>$url,
            'result'=>$result,
            'info'=>$info,
        );

        //$this->debug($info['curl_error']);
        if ($info['curl_errno']==0){

            $this->last_ans = json_decode($result, true);

            // 2022-09-07 Не уверен что на все запросы будет приходить ok=true, но пока так
            if (isset($this->last_ans['ok'])&& $this->last_ans['ok']==1){
                $this->logger->info(__CLASS__.'->'.__FUNCTION__.'() execute success.', ['method'=>$method]);
            }else{
                $this->logger->warning(__CLASS__.'->'.__FUNCTION__.'() execute probably failed.', ['method'=>$method, 'req'=>$params, 'res'=>$result]);
            }

            // Если телеграм ответил что сообщение длинное, то отправляем в безопасном режиме
            // Избегаем случай рекурсии
            if ($cut_mode==false && isset($this->last_ans['error_code']) && $this->last_ans['error_code']==400 && strpos($this->last_ans['description'], 'is too long') && !is_null($text)){
                $this->logger->warning('message too long, try to resend cutted');
                $this->last_ans = $this->query($method, array_merge($params, array('text'=>$text)), true); // игнорируем ответ от этой функции?
            }
            //echo $result;
            return $this->last_ans;
        }else{
            //print_r($info);
            $this->logger->warning(''.__CLASS__.'->'.__FUNCTION__.'() execute error '.$info['curl_errno'].':'.$info['curl_error']);
            $this->last_ans = $info; // тут можно упихать info
            //echo $result;

            /*
            file_put_contents(__DIR__."/../../var/log/telegram_fails.txt",
                "\r\n\r\n\r\n".'---------------------- '.date("Y-m-d H:i:S").' ----------------------'."\r\n".
                'send_to: '.$params['chat_id']."\r\n".
                'text: '.$text."\r\n".
                "\r\n".
                'ans: '."\r\n".
                print_r($this->last_ans, true));
            */

            return $this->last_ans;
        }
    }












    //https://apps.timwhitlock.info/emoji/tables/unicode
    /**
     * Если использовать код эмоджи в двойных кавычках, то он будет работать сам по себе "\xE2\x80\xBC "
     * Если в одинарных, то нужно вот такое вот преобразование
     * @param $text
     * @return array|string|string[]|null
     */
    public function  emoji($text){
        $pattern = '@\\\x([0-9a-fA-F]{2})@x';
        return $emoji = preg_replace_callback(
            $pattern,
            function ($captures){
                return chr(hexdec($captures[1]));
            },
            $text
        );
    }


    //////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// // NEW
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Прокси методы, возможно понадобятся
    //+
    public function getEvents($force=false){
        return $this->telegramEventRepository->getEvents($force);
    }
    //+
    public function getUsers($force=false){
        return $this->telegramUserRepository->getUsers($force);
    }

    public function findProjectUserByPhone($phone){
        return $this->telegramUserRepository->findProjectUserByPhone($phone);
    }

    public function findProjectUserBySuperuser(){
        return $this->telegramUserRepository->findProjectUserBySuperuser();
    }

    public function getUserEvent($user_chat_id, $event_name){
        return $this->telegramUserEventRepository->getUserEvent($user_chat_id, $event_name);
    }

    public function getUserEvents($user_chat_id){
        return $this->telegramUserEventRepository->getUserEvents($user_chat_id);
    }

    public function setActive($user_chat_id, $event_name, $active){
        return $this->telegramUserEventRepository->setActive($user_chat_id, $event_name, $active);
    }

    public function newUserEvent($user_chat_id, $event_name){
        return $this->telegramUserEventRepository->newUserEvent($user_chat_id, $event_name);
    }

    public function saveMessage($message, $update_id){
        $this->telegramMessageRepository->newMessage($message, $update_id);
    }

    public function checkUser($message){
        $this->telegramUserRepository->checkUser($message);
    }

    public function addUser($arr){
        $this->telegramUserRepository->addUser($arr);
    }

    public function updateUser($arr){
        $this->telegramUserRepository->updateUser($arr);
    }

    /**
     * Сделать пользователя суперюзером
     * @param $id
     */
    public function setSuperuser($id){
        $this->telegramUserRepository->setSuperuser($id);
    }


    public function setUserPhone($user_chat_id, $phone){
        $this->telegramUserRepository->setUserPhone($user_chat_id, $phone);
    }

    //public function setUser($user_chat_id, $id_user, $phone=''){
    public function linkUser($user_chat_id, $id_user, $phone=''){
        $this->telegramUserRepository->linkUser($user_chat_id, $id_user, $phone='');
    }



}
