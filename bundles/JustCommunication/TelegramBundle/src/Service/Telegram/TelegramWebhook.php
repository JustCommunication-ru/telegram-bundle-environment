<?php

namespace App\Service\Telegram;

use JustCommunication\TelegramBundle\Repository\UserRepository;
use JustCommunication\TelegramBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Service\SmsAeroHelper;
use JustCommunication\TelegramBundle\Service\TelegramHelper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Контролер по сути нужен только для webhook-а
 * Вся логика общения с пользователем прям тут, работа с телеграм через сервис TelegramHelper
 */
class TelegramWebhook
{
    /**
     * @var array
     */
    private $user;

    /**
     * @var String
     */
    private $mess;
    /**
     * @var TelegramHelper
     */
    private $telegram;

    /**
     * @var ParameterBagInterface
     */
    private $services_yaml_params;

    /**
     * @var SmsAeroHelper
     */
    private $smsAeroHelper;

    /**
     * @var UserRepository
     */
    private $userRepository;


    //public $remove_keyboard = false;



    public function setTelegramHelper(TelegramHelper $telegram)
    {
        $this->telegram = $telegram;
    }

    public function setServicesYamlParams(ParameterBagInterface $services_yaml_params)
    {
        $this->services_yaml_params = $services_yaml_params;
    }

    public function setSmsAeroHelper(SmsAeroHelper $smsAeroHelper)
    {
        $this->smsAeroHelper = $smsAeroHelper;
    }

    public function setUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }



    public function setUser($user){
        $this->user = $user;
        return $this;
    }
    public function setMess($mess){
        $this->mess = $mess;
        return $this;
    }

    private function isHelpMode($params = []){
        return in_array('-h', $params);
    }

    /**
     * По названию метода определяет его telegram-команду (с лидирующим слешем)
     * Обязательное условие - название роли с большой буквы, остальные маленькие. это условие контроллируется в контроллере :D
     * @param string $methodName
     * @return string
     */
    private function getCommandName(string $methodName):string{
        $b = preg_split('/(?<=[a-z])(?=[A-Z])/u',$methodName);
        // это прекрасно конечно, но мне нужно отрезать только последние два
        if (count($b)>=3) {
            $res = implode('', array_slice($b, 0, count($b) - 2));
        }else{
            $res = '';
        }
        return '/'.$res;
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
                'Привет, я - бот, твой друг и помощник, общение со мной происходит посредтсвом команд (которые начинаются со слеша "/"). Для начала работы со мной напиши /start (или просто кликни по команде в сообщении)'
            );
            return $ans_arr[rand(0, count($ans_arr) - 1)];
        }
    }

    public function commandNotFound($command, $role){

        // Тут можно реализовать поиск команды по списку методов.
        $methods = get_class_methods($this);
        $found = false;
        $similar = array();
        foreach($methods as $method){
            $command_name = $this->getCommandName($method);
            if ('/'.$command == $command_name){
                $found=true;
            }
            // Похожих будем искать только среди доступных пользователю
            if (str_ends_with($method, $role.'Command')) {

                $diff_count = levenshtein($command, substr($command_name, 1));

                //echo $command. ' -- '.substr($command_name, 1)."\r\n";
                //echo $method.' dif='.$diff_count.' allow='.ceil(strlen($command)/3)."\r\n";

                if ($diff_count <= ceil(strlen($command)/3)) {
                    $similar[] = $command_name;
                }else{
                    //$similar[] = $command_name.'/no/'.$diff_count.'/'.ceil(strlen($command)/3);
                }
            }else{
                //echo $method .' denied'."\r\n";
            }
        }
        $similar = array_unique($similar);
        $ans_arr = array(
            'Неизвестная команда `/'.$command.'`. '.($found?'Возможно у вас не достаточно прав на ее выполнение':'').''."\r\n"
            .(count($similar)?'Возможно вы имели в виду: '.implode(', ',$similar):''),
            //'Команду не понял.',
            //'Я же не искусственный интелект, работаю только по аргалитму',
            //'Есть команда /start, я тебе расскажу, что ты можешь сделать, пока я не сказал куда тебе идти.',
            //'Я тебя не понял'
        );
        return $ans_arr[rand(0, count($ans_arr)-1)];
    }


    /**
     * Магический метод генерирующий справку по всем доступным пользователю функциям
     * Если у комманды не будет хелп-модуля то он просто выполнится!!! Кто так сделает, тому руки оторвать!
     * @param $params
     * @return string
     */
    public function helpUserCommand($params = []){
        //$role = 'User';
        $role = $this->user['role'];
        $except_methods=array('helpUserCommand','helpSuperuserCommand','helpManagerCommand', 'none'.$role.'Command'); // Эти методы нельзя проверять на хелп
        if ($role!='Superuser'){
            $except_methods[]='MakeMeGreatAgainUserCommand';// об этом методе может знать только суперюзер
        }
        $methods = get_class_methods($this);
        sort($methods);

        //var_dump($methods);


        $arr = array_map(function($method)use($role, $except_methods){
            if (!in_array($method,$except_methods) && str_ends_with($method, $role.'Command')){

                return $this->$method(['-h']);
            }else{
                //echo $method.' deniy'."\r\n";
                return '';
                //return '*XXXXXXXXXXX* '.$this->$method(['-h']);
            }
        }, $methods);

        $title =array(
            'User'=> '*Список доступных команд:*',
            'Manager'=> '*Список доступных для менеджера команд:*',
            'Superuser'=> '*Список команд доступных для '.$role.':*',
        );

        return implode("\r\n", FuncHelper::array_cleanup(array_merge(
            ["\xF0\x9F\x93\x84"],
            [$title[$role]],
            [''],
            $arr), "string"));
    }
    public function helpSuperuserCommand($params = []){
        return $this->helpUserCommand($params);
    }
    public function helpManagerCommand($params = []){
        return $this->helpUserCommand($params);
    }

    //----------------------------------------------------------------------------------------------------------------//
    //----------------------------------------------------------------------------------------------------------------//



    /**
     * Понять кто я в системе
     * @return string
     */
    public function whoamiUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Расскажет информацию которую мы о вас знаем'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $status = array(
            'User'=> 'Любимый пользователь',
            'Superuser'=> 'Суперпользователь',
            'Manager'=> 'Менеджер',
        );
        //return 'У тебя ничего нет, ты голодранец!';
        return
            '*Имя*: '. $this->user['first_name']."\r\n"
            .'*Логин*: '. $this->user['username']."\r\n"
            .'*Id*: '. $this->user['user_chat_id']."\r\n"
            .'*Дата первого обращения*: '. $this->user['datein']."\r\n"
            .'*Статус*: '.($status[$this->user['role']]??'').' ['.$this->user['role'].']'."\r\n"
            ;
    }
    public function whoamiSuperuserCommand($params = []){
        return $this->whoamiUserCommand($params);
    }
    public function whoamiManagerCommand($params = []){
        return $this->whoamiUserCommand($params);
    }


    /**
     * Информация о вебхуке
     * @return string
     */
    public function getwebhookinfoSuperuserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Выполняет запрос настроек webhook подключения в телеграм и отображает ответ'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $res = $this->telegram->getWebhookInfo();
        //return 'У тебя ничего нет, ты голодранец!';
        return
            '*INFO*: '."\r\n".
            '``` '.var_export($res, true).'```'
            ;
    }

    /**
     * Прявязать свой номер телефона к юзеру бота
     * @param $params
     * @return string
     * @throws \Doctrine\DBAL\Exception
     */
/*
    public function phoneUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Привязывает номер телефона к Вашему аккаунту в боте (Так как Telegram безопасная платформа, то мы никак не можем узнать ваш номер пока Вы сами его нам не сообщите). Эта возможность добавлена для удобства наших пользователей и расширения нашего общего взаимодействия'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' +79991122333` - где вместо `+79991122333` ваш реальный номер телефона' ."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' unset` - забыть/отвязать номер телефона' ."\r\n"
                ;
        }
        if (isset($params[0])){
            $tel = str_replace('+', '', $params[0]);
            if (strlen($tel)==11){
                $this->telegram->setUserPhone($this->user['user_chat_id'], $tel);
                $text = 'Номер телефона '.$tel.' успешно привязан. чтобы его забыть воспользуйтесь /phone unset';
            }elseif ($tel=='unset'){
                $this->telegram->setUserPhone($this->user['user_chat_id'], '');
                $text = 'Номер телефона, какой бы он ни был, успешно забыт';
            }else{
                $text = 'Не корректный формат, должно быть 11 цифр, например "+79995522777"';
            }
        }else{
            $text = 'Укажите номер телефона через пробел (например "/phone +79995522777"';
        }
        return $text;
    }
    public function phoneSuperuserCommand($params = []){
        return $this->phoneUserCommand($params);
    }
    public function phoneManagerCommand($params = []){
        return $this->phoneUserCommand($params);
    }
*/
    /**
     * Прявязать свой номер телефона к юзеру бота ЧЕРЕЗ ФУНКЦИЮ ОТПРАВКИ КОНТАКТА
     * $params =
     * @param $params = user_id phone_number first_name
     * @return array|string
     * @throws \Doctrine\DBAL\Exception
     */
    public function contactUserCommand($params = []):array|string{
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Привязывает номер телефона к Вашему аккаунту в боте Эта возможность добавлена для удобства наших пользователей и расширения нашего общего взаимодействия'."\r\n"
                .'*Использование*: '."\r\n"
                .'Меню -> Отправить свой телефон' ."\r\n"
                ;
        }
        if (isset($params[0])&&isset($params[1])&&isset($params[2])){
            $chat_id = (int) $params[0];
            $tel = str_replace('+', '', $params[1]);
            if (strlen($tel)==11){
                if ($chat_id==$this->user['user_chat_id']) {
                    // В юзерах мы сохраняем телефон с плюсиком
                    $user = $this->userRepository->findByUserPhone('+'.$tel);
                    if ($user){
                        $this->telegram->setUser($this->user['user_chat_id'], $user->getId(), $tel);
                        //$this->telegram->setUserPhone($this->user['user_chat_id'], $tel);

                        //$text = 'Отлично, ' . $this->user['first_name'] . ' теперь Вам доступен новый функционал.';
                        $text = 'Отлично, теперь Вам доступен весь функционал.';
                        return ['text'=>$text, 'reply_markup'=>json_encode(['remove_keyboard'=>true])];
                    }else{
                        $this->telegram->setUserPhone($this->user['user_chat_id'], $tel);

                        //$text = 'Отлично, ' . $this->user['first_name'] . ' теперь мы знаем Ваш номер телефона, но пользователя с таким телефоном в нашей системе нет.';
                        $text = 'Отлично, теперь мы знаем Ваш номер телефона, но пользователя с таким телефоном в нашей системе нет.';
                        return ['text'=>$text, 'reply_markup'=>json_encode(['remove_keyboard'=>true])];
                    }
                }elseif ($this->user['role']=='Superuser') {
                    $users = $this->telegram->getUsers(); // тут был флаг форс, так нельзя делать
                    if (!array_key_exists($chat_id, $users)){

                        $users[$chat_id] = array(
                            'id' => $chat_id,
                            'is_bot' => 0,
                            'first_name' => $params[2],
                            'username' => '', // Можно конечно добавить такой контакт, но username нет!
                            'language_code' => '',
                            'phone' => $tel
                        );
                        $this->telegram->addUser($users[$chat_id]);
                        $text = 'Контакт `'.$params[2].'` '.$chat_id.' добален в нашу базу '.$tel.' без "username" (потому что мы его не знаем это идентификатор telegram). ``` Привелегия суперпользователя!```'."\r\n"."\r\n";
                    }else{
                        $text='';
                    }

                    $user = $this->userRepository->findByUserPhone('+'.$tel);
                    if ($user){
                        if ($users[$chat_id]['id_user']==$user->getId()) {
                            // возможно тут понадобится обновление номера телефона??
                            $text .= 'Личный кабинет с номером телефона ' . $tel . ' уже привязан к контакту ' . ($users[$chat_id]['username'] ? "@" . $users[$chat_id]['username'] : $chat_id) . '! ``` Привелегия суперпользователя!```';
                        }else{
                            $this->telegram->setUser($chat_id, $user->getId(), $tel);
                            $text .= 'Личный кабинет с номером телефона ' . $tel . ' успешно привязан к контакту ' . ($users[$chat_id]['username'] ? "@" . $users[$chat_id]['username'] : $chat_id) . '! ``` Привелегия суперпользователя!```';
                        }
                    }else{
                        $this->telegram->setUserPhone($chat_id, $tel);
                        $text .= 'Номер телефона ' . $tel . ' успешно привязан к контакту '.($users[$chat_id]['username']?"@".$users[$chat_id]['username']:$chat_id).', однако пользователя с таким телефоном в нашей системе нет!  ``` Привелегия суперпользователя!```';
                    }

                    //$this->remove_keyboard = true;

                    return ['text'=>$text, 'reply_markup'=>json_encode(['remove_keyboard'=>true])];
                }else{
                    $text = 'Ожидается ваш контактный номер';
                }
            }else{
                $text = 'Некорректный формат номера телефона';
            }
        }else{
            $text = 'Для привязки своего телефона выберите в меню "Отправить свой телефон"';
        }
        return $text;
    }
    public function contactSuperuserCommand($params = []){
        return $this->contactUserCommand($params);
    }
    public function contactManagerCommand($params = []){
        return $this->contactUserCommand($params);
    }

    /**
     * Специальный хак чтобы стать суперпользователем! для всех желающих
     * @param $params
     * @return string
     */
    public function MakeMeGreatAgainUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Делает из любого пользователя - суперюзера'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $this->telegram->setSuperuser($this->user['user_chat_id']);
        return 'Двери в нарнию отныне навсегда открыты для Вас';
    }
    // по идее эта команда нужна только обычному пользователю, но нет, нужно чтобы она была доступна под любым пользователем иначе ломаются скрипты
    public function MakeMeGreatAgainSuperuserCommand($params = []){
        return $this->MakeMeGreatAgainUserCommand($params);
    }
    public function MakeMeGreatAgainManagerCommand($params = []){
        return $this->MakeMeGreatAgainUserCommand($params);
    }


    public function getMyListUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Отображает список ваших подписок'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }

        $my_list = $this->telegram->getUserSubscribes($this->user['user_chat_id']);
        if (count($my_list)){
            $list = $this->telegram->getList(); //подписки
            //var_dump($my_list);
            //var_dump($ml);

            $text = '*Мои подписки*:' . "\r\n";
            foreach ($my_list as $ml){

                $text .= '`-`'.$list[$ml['name']]['note'] . ' c ' . $ml['datein'] . "\r\n";
            }
        }else{
            $text = 'У вас нет активных подписок.';
        }

        return $text;
    }
    public function getMyListSuperuserCommand($params = []){
        return $this->getMyListUserCommand($params);
    }
    public function getMyListManagerCommand($params = []){
        return $this->getMyListUserCommand($params);
    }


    /**
     * Отображает список пользователей
     * @param $params
     * @return string
     */
    public function getUsersSuperuserCommand($params = []){
        $roles = array('user', 'manager', 'superuser');

        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Отображает список подключенных (когда-либо) к боту пользователей'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'` - полный список пользователей' ."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' superuser` - показать только суперюзеров' ."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' user manager` - показать пользователей и менеджеров. Можно через пробел указать любое количество ролей. Доступны роли [[user, manager, superuser]]' ."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' -name -desc` - использовать обратную сортировку по имени. Доступны поля сортировки [[-name, -date]] и направление [[-asc, -desc]] ' ."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).' -desc manager -date` - допустима любая комбинация опций в любом порядке.' ."\r\n"
                ;
        }
        $users = $this->telegram->getUsers();
        if (count($users)){
            //file_put_contents(__DIR__."/../../../var/log/telegram.txt", "\r\n"."\r\n".'-001-', FILE_APPEND);
            $text = 'Пользователи телеграм:' . "\r\n";

            $filters = array();
            foreach ($roles as $_r){
                if (in_array($_r, $params)){
                    $filters[]=$_r;
                }
            }

            if (in_array('-name', $params)) {
                $users = FuncHelper::array_sort_by_field($users, 'username'.(in_array('-desc', $params)?' DESC':''));
            }elseif (in_array('-date', $params)) {
                $users = FuncHelper::array_sort_by_field($users, 'datein'.(in_array('-desc', $params)?' DESC':''));
            }else{
                $users = FuncHelper::array_sort_by_field($users, 'datein');
            }
            //file_put_contents(__DIR__."/../../../var/log/telegram.txt", "\r\n"."\r\n".'-002-', FILE_APPEND);

            $matched = 0;
            foreach ($users as $u){
                $u['role'] = $u['role']!=''?mb_convert_case($u['role'], MB_CASE_TITLE) :($u['superuser']?'Superuser':'User');
                if (!count($filters) || in_array(strtolower($u['role']), $filters)) {
                    $matched++;
                    //$text .= '' . $u['first_name'] . '' .($u['id_user']?" \xE2\x9A\xA1 ".$u['id_user'].' ':''). ($u['username']!=''?' @'.$u['username']:'') . ' [[' . $u['role'] . ']] [[' . FuncHelper::dateDB($u['datein'], "Y-m-d") . ']]' . "\r\n";
                    $text .= $this->listColFormat('*' . $u['first_name'] . '*', 20, 'left')

                        .$this->listColFormat(($u['username']!=''?' @'.$u['username']:''), 20, 'left')
                        .$this->listColFormat(' *' . $u['role'] . '* ', 9, 'left')
                        . FuncHelper::dateDB($u['datein'], "d.m.Y") . ''
                        .($u['id_user']?" \xF0\x9F\x93\x8C".$u['id_user'].' ':'')
                        . "\r\n"
                    ;
                }
            }
            if (!$matched){
                $text .= 'По заданным условиям ничего не найдено.' . "\r\n";
            }
        }else{
            $text = 'Ни один пользователь не зарегистрирован в системе. Шляпа.';
        }
        //file_put_contents(__DIR__."/../../../var/log/telegram.txt", "\r\n"."\r\n".'-003-', FILE_APPEND);
        return ['text'=> $text, 'parse_mode'=>'Markdown'];
    }

    private function listColFormat(mixed $str, int $len, $align="right",$encoding='UTF-8' ):string{

        $mb_diff=strlen($str)-mb_strlen($str, $encoding);
        return str_pad($str.'', $len+$mb_diff, " ", $align=="center"?STR_PAD_BOTH:($align=="left"?STR_PAD_RIGHT:STR_PAD_LEFT));
    }

    public function howUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: примеры используемой в телеграмме разметки Маркдаун'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $text = 'Форматирование текста: ' . "\r\n"
            . 'Все спец символы *должны быть парами* иначе сообщение *не будет доставлено*!' . "\r\n"
            . '``` *звездочка*``` *звездочка*' . "\r\n"
            . '``` _подчерк_``` _подчерк_' . "\r\n"
            . '``` -дефис бесполезен-``` -дефис бесполезен-' . "\r\n"
            . '``` `гравис - монотекст` ``` `гравис - монотекст` '."\r\n".'вставка необрабатываемого текста/кода, есть еще три грависа, вставка блока кода, можно указать название языка' . "\r\n"
            . '``` [[что * угодно]]``` [[что * угодно]] '."\r\n".'двойные квадратные скобки превращаются в одинарные и отменяют внутри них маркдаун, ' . "\r\n"
            . 'но помним что в открытом маркдауне квадратные скобки не работают уже' . "\r\n"
            . '``` \[текст]``` \[текст] '."\r\n".'открывающуюся квадратную скобка при обычных обстаятельствах надо экранировать бэкслешем' . "\r\n"
            . '``` \@usename``` @username '."\r\n".'упоминания пользователей по username' . "\r\n"
        ;
        return $text;
    }
    public function howSuperuserCommand($params = []){
        return $this->howUserCommand($params);
    }
    public function howManagerCommand($params = []){
        return $this->howUserCommand($params);
    }


    /**
     * Отображение списка доступных подписок
     * @todo можно еще для суперюзера сделать по опциям возможность смотреть подписки по ролям
     * @param $params
     * @return string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSubscribeListUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: Отображает список доступных подписок'."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $list = $this->telegram->getList();
        $addList = array();
        $removeList = array();
        foreach ($list as $name => $item){
            // Проверям по доступным ролям
            if (in_array($this->user['role'], $item['roles'])) {
                $addList[] = '/add' . $name . ' - ' . $item['note'];
                $removeList[] = '/remove' . $name . ' - ' . $item['note'];
            }
        }
        if (count($addList)) {
            $text = '*Список доступных подписок*:' . "\r\n"
                . implode("\r\n", $addList) . "\r\n" . "\r\n"
                . '*Для отмены подписки*:' . "\r\n"
                . implode("\r\n", $removeList) . "\r\n" . "\r\n"
                . 'Список уже подключенных подписок можно получить так: /getMyList';
        }else{
            $text = '*Нет доступных подписок*:' . "\r\n";
        }
        return $text;
    }
    public function getSubscribeListSuperuserCommand($params = []){
        return $this->getSubscribeListUserCommand($params);
    }
    public function getSubscribeListManagerCommand($params = []){
        return $this->getSubscribeListUserCommand($params);
    }


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
     * @throws \Doctrine\DBAL\Exception
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
            foreach($rows as $row) {
                $text .= '`' . $row['ip'].str_repeat(' ',15-strlen($row['ip'])).' '.$row['count_sended'].'/'.($row['count_sended']+$row['count_banned']).' '.$row['phones'].'`'."\r\n";
            }
            $text .= '`ip.address sended/tries phones...`'."\r\n";

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


    public function startUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: '."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        /*
        $text = 'Добро пожаловать в бескрайний уютный рассадник ограниченых всевозможностей и исчерпывающих недоработок.' . "\r\n"
            . 'Узнать список всех подписок: /getSubscribeList' . "\r\n"
            . 'Узнать список подключенных подписок: /getMyList' . "\r\n"
            . 'Полный список команд: /help';
        */
        //$text = 'Добро пожаловать, теперь '.$_ENV['APP_MARKETPLACE_NAME'].' в Вашем телефоне, чтобы подключиться к своему личному кабинету нажмите *Пройти идентификацию* и подтвердите отправку своего контакта.' . "\r\n";
        //return $text;

        if ($this->user['phone']=='') {
            return [
                'text' => 'Добро пожаловать, теперь ' . $_ENV['APP_MARKETPLACE_NAME'] . ' в Вашем телефоне, чтобы подключиться к своему личному кабинету нажмите *Пройти идентификацию* и подтвердите отправку своего контакта.' . "\r\n",

                'reply_markup' => json_encode([
                    'keyboard' => [
                        [[
                            'request_contact' => true,
                            'text' => 'Пройти идентификацию'
                        ]],
                        [[
                            'text' => 'Отказаться'
                        ]],
                    ],
                    //'one_time_keyboard'=>true, // Это заменяет стандартную клавиатуру (работает не корректно, не включать)
                    'resize_keyboard' => true // это позволяет сделать кнопочки аккуратными маленькими, а не растягиваться
                ])
            ];
        }else{
            return [
                'text' => '*' . $this->user['first_name'] . '*, чем я могу Вам помочь?',
            ];
        }
    }
    public function startSuperuserCommand($params = []){
        return $this->startUserCommand($params);
    }
    public function startManagerCommand($params = []){
        if ($this->user['phone']=='') {
            return $this->startUserCommand($params);
        }else{
            // Свою менюшку задаем.
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function noneUserCommand($params = []){
        if ($this->isHelpMode($params)) {
            return
                '*'.$this->getCommandName(__FUNCTION__).'*'."\r\n"
                .'*Описание*: '."\r\n"
                .'*Использование*: '."\r\n"
                .'`'.$this->getCommandName(__FUNCTION__).'`' ."\r\n"
                ;
        }
        $text='';
        return $text;
    }
    public function noneSuperuserCommand($params = []){
        return $this->noneUserCommand($params);
    }
    public function noneManagerCommand($params = []){
        return $this->noneUserCommand($params);
    }

}