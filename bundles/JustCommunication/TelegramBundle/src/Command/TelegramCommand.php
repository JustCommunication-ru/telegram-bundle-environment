<?php

namespace App\Command;

use App\Controller\TelegramController;
use App\Kernel;
use App\Repository\ClientPriceInRepository;
use App\Service\CacheHelper;
use App\Service\FuncHelper;
use App\Service\SolrProducts;
use App\Service\TelegramHelper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Функционал работы с телеграм ботом на серверной стороне.
 * Здесь же методы для крон задач.
 */
class CommandTelegram extends CommonCommand
{

    public $config;
    public $telegram;
    public $db;
    public $cache;

    public function __construct(ParameterBagInterface $params, TelegramHelper $telegramHelper, Connection $connection, CacheHelper $cacheHelper, KernelInterface $kernel, HttpClientInterface $client)
    {
        // а надо ли?
        $this->config = $params->get('telegram');
        $this->telegram = $telegramHelper;
        $this->db = $connection;
        $this->cache = $cacheHelper->getCache();
        $this->kernel = $kernel;

        $this->curl = $client;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:telegram')
            ->setDescription('Description')
            ->setHelp('Help')

            // Опции это то что с двумя дефисами --update, значения через пробел, если не указать будет ругаться!
            ->addOption('d', null, InputOption::VALUE_NONE, 'Debug mode, show verbose log')
            ->addOption('setWebhook', null, InputOption::VALUE_NONE, 'Action. Say to Telegram use configured webhook url (services.yaml)')
            ->addOption('delWebhook', null, InputOption::VALUE_NONE, 'Action. ')
            ->addOption('sendContact', null, InputOption::VALUE_NONE, 'Action. ')
            ->addOption('init', null, InputOption::VALUE_NONE, 'Action. Init db data, check and link admin contact')

            ->addOption('getWebhookInfo', null, InputOption::VALUE_NONE, 'Action. Get from Telegram webkook parameters.')
            ->addOption('getUpdates', null, InputOption::VALUE_NONE, 'Action. get updates from Telegram.')
            ->addOption('send', null, InputOption::VALUE_OPTIONAL, 'Action. Send message by chat id. Use --mess/-m for text', 'EMPTY')
            ->addOption('event', null, InputOption::VALUE_OPTIONAL, 'Action (+value). Send message by event name. Use --mess/-m for text', 'EMPTY')
            ->addOption('initlist', null, InputOption::VALUE_NONE, 'Action. debug:init telegram_list')
            ->addOption('clearlist', null, InputOption::VALUE_NONE, 'Action. debug:clear telegram_list')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Action. debug: foo')
            ->addOption('webhook', null, InputOption::VALUE_OPTIONAL, 'Action. Imitation of chat with bot, set your message in quotes!', 'EMPTY')

            ->addOption('id', 'c', InputOption::VALUE_OPTIONAL,  'Option for send. Chat id to send message to')
            ->addOption('name', null , InputOption::VALUE_OPTIONAL,  'Option for send. Event name to send message to')
            ->addOption('mess', 'm', InputOption::VALUE_OPTIONAL,  'Option for send. Text message for send')
            ->addOption('phoneFrom','pf', InputOption::VALUE_OPTIONAL, "Option for send. Phone user from whom send message. Use with webhook option")
            // Это для отправки контакта на вебхук, использовать только если понимаешь что делаешь
            ->addOption('contact', null, InputOption::VALUE_NONE, 'Option for webhook. Send contact, format: "chat_id phone_number name"')




            // InputArgument::OPTIONAL
            // InputArgument::REQUIRED
            // аргумент, это то что будет идти неименовано по порядку!
            //->addArgument('id', InputArgument::OPTIONAL, 'chat id to send message to')
            //->addArgument('mess', InputArgument::OPTIONAL, 'message text')
            //->addArgument('id_client', InputArgument::OPTIONAL, 'id_client')

        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->io = new SymfonyStyle($input, $output);
        $this->dev_debug_input = $input;

        /*
        // если включена отладка
        if ($input->getOption('d')) {
            // Передаем в solr_product колбэк для отображения отладочной инфы
            $this->solr_products->debug_callback = function($str){
                $this->io->warning($str);
            };
        }
        */
/*
        if($input->hasOption('event')) {
            $this->io->info('hasOption');
        }
        if($input->hasParameterOption('event')) {
            $this->io->info('hasParameterOption');
        }
        if($input->hasParameterOption('event')) {
            $this->io->info('hasParameterOption');
        }

        var_dump($input->getOption('event'));
        var_dump($input->getOptions());


        $this->io->error('end');
*/

        if ($input->getOption('init')) {
            $this->io->info('Action: init');

            // Юзаем функцию как генератор. Экзотическое решение, можно было просто на return-ах сделать
            $step_by_step = $this->telegramInit();
            foreach($step_by_step as $step){
                if (!$step){
                    break;
                }
            }

        }elseif ($input->getOption('setWebhook')) {
            $this->io->info('Action: setWebhook');

            $res = $this->telegram->setWebhook();
            $this->io->success(['setWebhook query result:', print_r($res, true)]);
        }elseif ($input->getOption('getWebhookInfo')) {
            $this->io->info('Action: getWebhookInfo');

            $res = $this->telegram->getWebhookInfo();
            $this->io->success(['getWebhookInfo ok?', print_r($res, true)]);
        }elseif ($input->getOption('send')!='EMPTY') {
            $this->io->info('Action: send message');

            if (!$input->getOption('send')){
                $this->io->error('You need to set chat_id after --send option, for example: --send 537830154');
            }elseif ($input->getOption('mess')){
                $chat_id = $input->getOption('send');
                $mess = $input->getOption('mess');

                $res = $this->telegram->sendMessage($chat_id, $mess);
                if (isset($res['ok'])&&$res['ok']){
                    $this->io->success('Send success, message_id: '.$res['result']['message_id']);
                }else{
                    $this->io->error(['Send fail', json_encode($res)]);
                }

            }else{
                $this->io->error('You need to set message (--mess) option');
            }
        }elseif ($input->getOption('event')!='EMPTY') {

            $this->io->info('Action: send event message');

            if (!$input->getOption('event')){
                $this->io->error('You need to set event name after --event option, for example: --event Error');
            }elseif ($input->getOption('mess')){
                $event_name = $input->getOption('event');
                $mess = $input->getOption('mess');

                $res_arr = $this->telegram->event($event_name, $mess);
                $success_send = 0 ;
                foreach ($res_arr as $res){
                    if (isset($res['ok'])&&$res['ok']){
                        $success_send++;
                    }
                }
                if ($success_send>0){
                    $this->io->success('Send success ('.$success_send.'/'.count($res_arr).'), message_id: '.$res['result']['message_id']);
                }else{
                    $this->io->error(['Send all fail', json_encode($res)]);
                }

            }else{
                $this->io->error(['You need to set message (--mess) options']);
            }
        }elseif ($input->getOption('initlist')) {
            $this->io->info('Action: initlist');
/*
            $list = $this->telegram->getList(true);

            if (count($list)==0){
                $this->db->executeStatement('INSERT INTO telegram_list SET name=?, note=?',
                    array_values(array('name' => 'Error', 'note' => 'Подписка на ошибки сервера')));
                $this->db->executeStatement('INSERT INTO telegram_list SET name=?, note=?',
                    array_values(array('name' => 'Report', 'note' => 'Подписка на какие-то отчеты, я еще не придумал какие')));
                $this->db->executeStatement('INSERT INTO telegram_list SET name=?, note=?',
                    array_values(array('name' => 'Test', 'note' => 'Тестовая подписька')));
                $this->cache->delete('telegram_list');

                $this->io->success('Inserted successfully');
            }else{
                $this->io->success('Already filled');
            }
            */
            $this->io->caution('DEPRECATED переделать, list, roles
            ');
        }elseif ($input->getOption('clearlist')) {
            $this->io->info('Action: clearlist');
            $this->db->executeStatement('DELETE FROM telegram_list WHERE id>0');
            $this->cache->delete('telegram_list');
            $this->io->success('Inserted successfully');
        }elseif ($input->getOption('test')) {
            $this->io->info('Action: test');

            //$user_id = 537830154;
            //$possible_list_name = 'Error';
            //$row = $this->telegram->getUserSubscribe($user_id, $possible_list_name);
            //var_dump($row);
            $this->io->success('end');

        }elseif ($input->getOption('delWebhook')) {
            $this->io->info('Action: delWebhook');
            $row = $this->telegram->delWebhook();
            $this->io->success('end');

        }elseif ($input->getOption('getUpdates')) {
            $this->io->info('Action: getUpdates');
            $row = $this->telegram->getUpdates();
            var_dump($row);
            $this->io->success('end');

        }elseif ($input->getOption('webhook')!='EMPTY') {

            if (is_null($input->getOption('webhook'))){
                if ($input->getOption('contact')){
                    $this->io->warning([
                        'need contact data --webhook "456456456 +79021112233 VasyaPupkin", for example',
                    ]);
                }else {
                    $this->io->warning([
                        'You should type mess to telegram webhook after --webhook "/somecomand", for example',
                    ]);
                }
            }else {

                $this->io->info('Action: talk with chat bot');

                $this->io->success('mess: ' . $input->getOption('webhook'));


                if ($input->getOption('contact')){

                    $arr = explode(' ', $input->getOption('webhook'));
                    $paramaters = array(
                        'update_id' => 1,
                        'message' => array(
                            'message_id' => 1,
                            'from' => array(
                                'id' => $this->telegram->getAdminChatId(),
                                'first_name' => 'CommandUser',
                                'username' => 'Commandusername'
                            ),
                            'contact' => array(
                                'phone_number' => $arr[1]??0,
                                'first_name'=>$arr[2]??'SomePersonName',
                                'user_id'=>$arr[0]>0?$arr[0]:$this->telegram->getAdminChatId()
                            ),
                            'date' => date("U"),
                        )
                    );
                }else {


                    //$tc = new TelegramController();
                    //$tc->telegram_webhook($this->telegram);

                    // как вызвать контроллер из команда?


                    //$env  = $this->kernel->getEnvironment();

                    //$kernel = new Kernel($env, true);


                    //phoneFrom

                    $chatId = $this->telegram->getAdminChatId();
                    if($input->getOption('phoneFrom')){
                        $chatId = $this->telegram->findByPhone($input->getOption('phoneFrom')) ;
                    }

                    $paramaters = array(
                        'update_id' => 1,
                        'message' => array(
                            'message_id' => 1,
                            'from' => array(
                                'id' => $chatId,
                                'first_name' => 'CommandUser',
                                'username' => 'Commandusername'
                            ),
                            'text' => $input->getOption('webhook'),
                            'date' => date("U"),
                            'entities' => array()
                        )
                    );
                }
                $_GET['command_request'] = '1';
                $_GET['token'] = $this->telegram->config['token'];

                //$request = Request::create('/telegram/webhook?token='.$this->telegram->config['token'], 'POST', $paramaters);
                $request = Request::create('/telegram/webhook', 'POST', array(), array(), array(), array(), json_encode($paramaters));
                //$request = Request::create('/telegram/webhook', 'GET', array('sdfs'=>'dgsdfg'), array(), array(), array(), json_encode($paramaters));
                $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

                die($response->getContent());


                /*
                // Проверка боевого
                $response = $this->curl->request(
                    'POST',
                    'https://service.espvbprr.jc9.ru/telegram/webhook?token=1563625748:AAGn-bBMpbYy2iEeXOYnbhDx8HOrr7VJvJw',
                    ['body' => json_encode($paramaters)]
                );
                die($response->getContent());
                */

            }


        }else{
            //$this->io->warning($this->config);
            $this->io->warning([
                'Choose needed options for action (-h in case of help)',
            ]);
        }
        return Command::SUCCESS;
    }

    /**
     * Пощаговая проверка/настройка конфигов телеграма в виде генератора
     * @return \Generator
     */
    private function telegramInit(){

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 1) проверка админа
        //$x = $this->io->ask('Admin telegram contact (admin_chat_id):' . $admin_chat_id, '5555');
        $all_is_good_go_on_dude = 1;
        $admin_chat_id = (int)$this->telegram->config["admin_chat_id"];

        if ($admin_chat_id>10000000) {
            $this->io->success(['Admin telegram contact (admin_chat_id) by .env:', $admin_chat_id]);
        }else{
            $this->io->caution(['Warning: please set in .env.local your real chat_id of telegram, like this:', 'TELEGRAM_ADMIN_CHAT_ID=537830154']);
            $this->io->block(['https://www.google.com/search?q=%D0%BA%D0%B0%D0%BA+%D1%83%D0%B7%D0%BD%D0%B0%D1%82%D1%8C+%D1%81%D0%B2%D0%BE%D0%B9+%D1%87%D0%B0%D1%82+id+%D0%B2+%D1%82%D0%B5%D0%BB%D0%B5%D0%B3%D1%80%D0%B0%D0%BC%D0%BC%D0%B5']);

            $all_is_good_go_on_dude=false;
        }
        yield $all_is_good_go_on_dude;

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 2) проверка связи с телеграммом
        $all_is_good_go_on_dude=2;

        $res = $this->telegram->getWebhookInfo();
        if ($res) {
            if ($res['ok']){
                $this->io->success(['Real telegram server connection success ('.$this->telegram->config["url"].')']);

                if ($_ENV['APP_ENV']=='dev'){
                    if (!isset($_ENV['MODULES_TELEGRAM_WEBHOOK_SERVER_FOR_DEV']) || $_ENV['MODULES_TELEGRAM_WEBHOOK_SERVER_FOR_DEV']==''){
                        $this->io->caution(['Warning, not set param MODULES_TELEGRAM_WEBHOOK_SERVER_FOR_DEV in in .env / .env.local',
                            'for example MODULES_TELEGRAM_WEBHOOK_SERVER_FOR_DEV="https://service.espvbprr.jc9.ru"',
                            'Note, it`s only LOCAL APP_ENV=DEV configuration requirements']);
                        yield false;
                    }
                }

                if (isset($res['result']['url']) && $res['result']['url'] == $this->telegram->getWebhookUrl()) {
                    $this->io->success(['Webhook for '.$_ENV['MODULES_TELEGRAM_BOT_NAME'].' already set correctly:', $res['result']['url']]);
                } else {
                    $res_set_wh = $this->telegram->setWebhook();
                    if ($res_set_wh && $res_set_wh["ok"]){
                        $this->io->success(['Webhook for '.$_ENV['MODULES_TELEGRAM_BOT_NAME'].' now set successfully:', $this->telegram->getWebhookUrl()]);
                    }else{
                        $this->io->caution(['Warning: setWebhook has bad response', var_export($res_set_wh, true)]);
                        $all_is_good_go_on_dude=false;
                    }
                }
            }else{
                $this->io->caution(['Warning: bad response from telegram server, wrong pair "MODULES_TELEGRAM_BOT_NAME" / "MODULES_TELEGRAM_TOKEN"']);
                $this->io->block(['Check or set in .env / .env.local "MODULES_TELEGRAM_BOT_NAME"']);
                $this->io->block(['Check or set in .env.local "MODULES_TELEGRAM_TOKEN"']);
                $all_is_good_go_on_dude=false;
            }
        }else{
            $this->io->caution(['Warning: network error connect to telegram server ('.$this->telegram->config["url"].'), check your internet or link above.']);
            $all_is_good_go_on_dude=false;
        }
        yield $all_is_good_go_on_dude;

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 3) Проверка отправки сообщения админу
        $all_is_good_go_on_dude=3;

        $mess = "*Command.app.telegram.init* Проверка отправки сообщения из ".$_ENV['APP_NAME']. ' для администратора';
        $res_send = $this->telegram->sendMessage($admin_chat_id, $mess);
        if ($res_send) {
            if ($res_send['ok']) {
                $this->io->success(['Message send successfuly to '. $admin_chat_id]);
            }else{
                $this->io->caution(['Warning: setWebhook has bad response', var_export($res_send, true)]);
                $all_is_good_go_on_dude=false;
            }
        }else{
            $this->io->caution(['Warning: network error connect to telegram server on send message.']);
            $all_is_good_go_on_dude=false;
        }

        yield $all_is_good_go_on_dude;

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 4) Проверка и перезапись справочных таблиц telegram_list telegram_users_list
        $all_is_good_go_on_dude=4;

        $preset = array(
            'Error'=>array('note'=>'Подписка на ошибки сервера', 'roles'=>array("Superuser")),
            'Neworder'=>array('note'=>'Подписка на создание и изменение заказа', 'roles'=>array("Superuser","Moderator")),
            'Payorder'=>array('note'=>'Подписка на оплату заказа для бухгалтера', 'roles'=>array("Superuser","Moderator")),
            'Report'=>array('note'=>'Подписка на отчеты', 'roles'=>array("Superuser")),
            'Category'=>array('note'=>'Подписка на изменения категорий при парсинге прайсов', 'roles'=>array("Moderator")),
            'Kkm'=>array('note'=>'Подписка на ошибки KKM', 'roles'=>array("Superuser")),
            'Smsover'=>array('note'=>'Подписка на ошибки лимита смс', 'roles'=>array("Superuser")),
        );

        $list = $this->telegram->getList(true);
        $_changes = ['Table "telegram_list" update successfuly:'];
        foreach ($preset as $name=>$item){
            if (isset($list[$name])){
                if ($list[$name]['note']==$item['note'] && json_encode($list[$name]['roles'])==json_encode($item['roles'])) {
                    $_changes[$name] = '"'.$name.'" is up to date';
                }else{
                    $this->db->executeStatement('UPDATE telegram_list SET `note`=?, `roles`=? WHERE `name`=?',
                        array_values(array('note' => $preset[$name]['note'], 'roles' => json_encode($preset[$name]['roles']), 'name' => $name)));
                    $_changes[$name] = '"'.$name.'" was updated';
                }
            }else{
                $this->db->executeStatement('INSERT INTO telegram_list SET `note`=?, `roles`=?, `name`=?',
                    array_values(array('note' => $preset[$name]['note'], 'roles'=>json_encode($preset[$name]['roles']), 'name' => $name)));
                $_changes[$name] = '"'.$name.'" was inserted';
            }
        }
        $this->cache->delete('telegram_list');
        $this->io->success($_changes);

        yield $all_is_good_go_on_dude;


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 5) Прописка пользователя телеграма
        $all_is_good_go_on_dude=5;

        $users = $this->telegram->getUsers(true); // в этот момент мы уже схоронили себе этого пользователя, так что он на 146% у нас есть

        if (isset($users[$admin_chat_id]) && $users[$admin_chat_id]['superuser']==1){
            $this->io->success(['Table "telegram_users" ok, telegram admin user already in database and superuser']);
        }else{
            $paramaters = array(
                'update_id' => 1,
                'message' => array(
                    'message_id' => 1,
                    'from' => array(
                        'id' => $admin_chat_id,
                        'first_name' => 'MarketplaceAdmin',
                        'username' => 'Marketplaceadmin'
                    ),
                    'text' => '/MakeMeGreatAgain',
                    'date' => date("U"),
                    'entities' => array()
                )
            );
            $_GET['command_request'] = '1';
            $_GET['token'] = $this->telegram->config['token'];
            $request = Request::create('/telegram/webhook', 'POST', array(), array(), array(), array(), json_encode($paramaters));
            $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

            $res = json_decode($response->getContent(), true);
            if ($res && isset($res['result']) && $res['result']=='ok'){
                $users_new = $this->telegram->getUsers(true);
                if (isset($users_new[$admin_chat_id]) && $users_new[$admin_chat_id]['superuser']==1){
                    if (isset($users[$admin_chat_id])){
                        $this->io->success(['Table "telegram_users" updated, telegram admin user is superuser now']);
                    }else{
                        $this->io->success(['Table "telegram_users" updated, telegram admin user was inserted succesfully']);
                    }
                    $users = $users_new;
                }else{
                    $this->io->caution(['Warning: telegram admin user insert failed.']);
                    $all_is_good_go_on_dude=false;
                }
            }else{
                $this->io->caution(['Warning: network error connect to telegram server on MakeMeGreatAgain.']);
                $all_is_good_go_on_dude=false;
            }
        }
        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 6) Проверка пользователя маркетплейса С КАКИМ НОМЕРОМ ТЕЛЕФОНА?? Ищем первого попавшегося суперюзера
        $all_is_good_go_on_dude=6;

        if ($users[$admin_chat_id]['id_user']>0){
            $this->io->success(['Table "telegram_users" ok, telegram admin user already linked with user #'.$users[$admin_chat_id]['id_user']]);
        }else{

            $statement = $this->db->prepare('SELECT *  FROM user WHERE JSON_CONTAINS(roles,\'"ROLE_SUPERUSER"\',"$")=1 ORDER BY id ASC LIMIT 1');
            $result = $statement->executeQuery();
            $rows = $result->fetchAllAssociative();

            if (is_array($rows)){
                $user = array_pop($rows);
                $this->io->success(['Superuser found in table "user" with phone '.$user['phone']]);

                $paramaters = array(
                    'update_id' => 1,
                    'message' => array(
                        'message_id' => 1,
                        'from' => array(
                            'id' => $admin_chat_id,
                            'first_name' => 'MarketplaceAdmin',
                            'username' => 'Marketplaceadmin'
                        ),
                        'contact' => array(
                            'phone_number' => $user['phone'],
                            'first_name' => 'MarketplaceAdmin',
                            'user_id'=>$admin_chat_id
                        ),
                        'date' => date("U"),
                    )
                );
                $_GET['command_request'] = '1';
                $_GET['token'] = $this->telegram->config['token'];
                $request = Request::create('/telegram/webhook', 'POST', array(), array(), array(), array(), json_encode($paramaters));
                $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

                $res = json_decode($response->getContent(), true);
                if ($res && isset($res['result']) && $res['result']=='ok'){
                    $users_new = $this->telegram->getUsers(true);
                    if (isset($users_new[$admin_chat_id]) && $users_new[$admin_chat_id]['id_user']==$user['id']){
                        $this->io->success(['Table "telegram_users" updated, telegram admin user linked with user #'.$user['id']]);
                    }else{
                        $this->io->caution(['Warning: send contact failed.']);
                        $all_is_good_go_on_dude=false;
                    }
                }else{
                    $this->io->caution(['Warning: network error connect to telegram server on send contact.']);
                    $all_is_good_go_on_dude=false;
                }

            }else{
                $this->io->caution(['Warning: user with role = ROLE_SUPERUSER not found in table "user".']);
                $all_is_good_go_on_dude=false;
            }
        }

        //'JSON_CONTAINS(roles,'"AUTH"','$')=1';
        // ROLE_SUPERUSER
        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 7) Подписываемся на всё что можно, можно через апи телеграм сделать, можно прям тут ручками
        $all_is_good_go_on_dude=7;

        //$this->telegram->getUserSubscribes($admin_chat_id) не подходит, потому что надо с active=0 тоже
        $statement = $this->db->prepare('SELECT id, user_chat_id, name, active, DATE_FORMAT(datein, "%d.%m.%Y") as datein FROM telegram_users_list WHERE user_chat_id='.$admin_chat_id.'');
        $result = $statement->executeQuery();
        $mylist = FuncHelper::array_foreach($result->fetchAllAssociative(), true, 'name');

        $__changes = ['Subscribe admin for all list. Table "telegram_users_list" update successfuly:'];
        foreach ($preset as $name=>$item){
            if (isset($mylist[$name])){
                if ($mylist[$name]['active']==1) {
                    $__changes[$name] = '"'.$name.'" already subscribed';
                }else{
                    $this->db->executeStatement('UPDATE telegram_users_list SET active=1 WHERE `user_chat_id`=? AND `name`=?',
                        array_values(array('user_chat_id' => $admin_chat_id, 'name' => $name)));
                    $__changes[$name] = '"'.$name.'" subscribe activated';
                }
            }else{
                $this->db->executeStatement('INSERT INTO telegram_users_list SET `datein`=now(), `user_chat_id`=?, `name`=?, `active`=1',
                    array_values(array('user_chat_id' => $admin_chat_id, 'name' => $name)));
                $__changes[$name] = '"'.$name.'" subscribed successfuly';
            }
        }
        $this->io->success($__changes);

        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// Отправка контакта (привязка телефона к контакту) админа
        $all_is_good_go_on_dude=9;

        foreach ($preset as $name=>$item) {
            $this->telegram->event($name, 'Сообщение на событие "'.$name.'"');
        }

        $this->io->success(['telegram events sended ('.implode(', ', array_keys($preset)).')']);
        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// Отправка контакта (привязка телефона к контакту) админа
        $all_is_good_go_on_dude=10;

            $this->io->success(['All done. Congratulation!']);
        yield $all_is_good_go_on_dude;



    }


}