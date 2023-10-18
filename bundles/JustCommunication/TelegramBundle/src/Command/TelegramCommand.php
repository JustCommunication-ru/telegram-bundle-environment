<?php

namespace JustCommunication\TelegramBundle\Command;

use Exception;
use Generator;
use JustCommunication\TelegramBundle\Repository\TelegramEventRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserRepository;
use JustCommunication\TelegramBundle\Repository\TelegramUserEventRepository;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class TelegramCommand extends Command
{
    private TelegramHelper $telegram;
    private KernelInterface $kernel;
    private TelegramUserRepository $telegramUserRepository;
    private TelegramEventRepository $telegramEventRepository;
    private TelegramUserEventRepository $telegramUserEventRepository;
    private UrlGeneratorInterface $router;
    private InputInterface $input;

    public function __construct(
                                TelegramHelper $telegramHelper,
                                KernelInterface $kernel,
                                TelegramUserRepository $telegramUserRepository,
                                TelegramEventRepository $telegramEventRepository,
                                TelegramUserEventRepository $telegramUserEventRepository,
                                UrlGeneratorInterface $router
    )
    {
        parent::__construct();

        $this->telegram = $telegramHelper;
        $this->kernel = $kernel;
        $this->telegramUserRepository = $telegramUserRepository;
        $this->telegramEventRepository = $telegramEventRepository;
        $this->telegramUserEventRepository = $telegramUserEventRepository;

        //$this->curl = $client;
        $this->router = $router;

    }

    protected function configure()
    {

        $this
            ->setName('jc:telegram')
            ->setDescription('Description')
            ->setHelp('Help')

            // Опции это то что с двумя дефисами --update, значения через пробел, если не указать будет ругаться!
            ->addOption('init', null, InputOption::VALUE_NONE, 'Action. Init db data, check and link admin contact')
            ->addOption('testw', null, InputOption::VALUE_NONE, 'Action. Test Webhook methods')


            ->addOption('d', null, InputOption::VALUE_NONE, 'Debug mode, show verbose log')
            ->addOption('setWebhook', null, InputOption::VALUE_NONE, 'Action. Say to Telegram use configured webhook url (services.yaml)')
            ->addOption('delWebhook', null, InputOption::VALUE_NONE, 'Action. ')
            ->addOption('sendContact', null, InputOption::VALUE_NONE, 'Action. ')


            ->addOption('getWebhookInfo', null, InputOption::VALUE_NONE, 'Action. Get from Telegram webkook parameters.')
            ->addOption('getUpdates', null, InputOption::VALUE_NONE, 'Action. get updates from Telegram.')
            ->addOption('send', null, InputOption::VALUE_OPTIONAL, 'Action. Send message by chat id. Use --mess/-m for text', 'EMPTY')
            ->addOption('event', null, InputOption::VALUE_OPTIONAL, 'Action (+value). Send message by event name. Use --mess/-m for text', 'EMPTY')
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
        //$output->writeln('LALALA');
        $this->io = new SymfonyStyle($input, $output);
        $this->dev_debug_input = $input;
        $this->input = $input;


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
                    //$this->io->success('Send success ('.$success_send.'/'.count($res_arr).'), message_id: '.$res['result']['message_id']);
                    $this->io->success('Send success ('.$success_send.'/'.count($res_arr).')');
                }else{
                    $this->io->error(['Send all fail'. count($res_arr)]);
                }

            }else{
                $this->io->error(['You need to set message (--mess) options']);
            }

        }elseif ($input->getOption('test')) {
            $this->io->info('Action: test');

            //$user_id = 537830154;
            //$possible_list_name = 'Error';
            //$row = $this->telegram->getUserSubscribe($user_id, $possible_list_name);
            //var_dump($row);
            $this->io->success('end');

        }elseif ($input->getOption('delWebhook')) {
            $this->io->info('Action: delWebhook');
            $this->telegram->delWebhook();
            $this->io->success('end');

        }elseif ($input->getOption('getUpdates')) {
            $this->io->info('Action: getUpdates');
            $row = $this->telegram->getUpdates();
            var_dump($row);
            $this->io->success('end');

        }elseif ($input->getOption('testw')) {
            $this->io->info('Action: testw (Test Webhook Commands)');
            $this->telegramTestWebhookommands();

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

                        $user = $this->telegramUserRepository->findOneBy(['phone'=>str_replace("+", "", $input->getOption('phoneFrom'))]);
                        if ($user){
                            $chatId = $user->getUserChatId();
                        }
                        //chatId = $this->telegram->findByPhone($input->getOption('phoneFrom')) ;
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

                $webhook_url_path = $this->router->generate('jc_telegram_webhook');
                $request = Request::create($webhook_url_path, 'POST', array(), array(), array(), array(), json_encode($paramaters));
                $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

                $res = $response->getContent();
                $res_arr = json_decode($res, true);
                if ($res_arr!==null) {
                    var_dump($res_arr);
                }else{
                    echo $res;
                }
                die("\r\n"."\r\n");
            }

        }else{
            //$this->io->warning($this->telegram->config);
            $this->io->warning([
                'Choose needed options for action (-h in case of help)',
            ]);
        }
        return Command::SUCCESS;
    }


    /**
     * Пошаговая проверка/настройка конфигов телеграма в виде генератора
     * @return Generator
     * @throws Exception
     */
    private function telegramInit(): Generator
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 1) проверка админа
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

                if (isset($res['result']['url']) && $res['result']['url'] == $this->telegram->getWebhookUrl()) {
                    $this->io->success(['Webhook for '.$this->telegram->config['bot_name'].' already set correctly:', $res['result']['url']]);
                } else {
                    $this->io->warning(['Webhook for '.$this->telegram->config['bot_name'].' not set correctly: ', $res['result']['url']]);

                    if ($_ENV['APP_ENV']!='dev' || $this->telegram->config['production_webhook_app_url']!='') {
                        $res_set_wh = $this->telegram->setWebhook();
                        if ($res_set_wh && $res_set_wh["ok"]) {
                            $this->io->success(['Webhook for ' . $this->telegram->config['bot_name'] . ' now set successfully:', $this->telegram->getWebhookUrl()]);
                        } else {
                            $this->io->caution(['Warning: setWebhook ('.$this->telegram->getWebhookUrl().') has bad response', var_export($res_set_wh, true)]);
                            $all_is_good_go_on_dude = false;
                        }
                    }else{
                        $this->io->warning(['Warning, param "telegram.config.production_webhook_app_url" is empty in packages/telegram.yaml',
                            'it must be a real URL of your project in www',
                            'Configuration your telegram bot webhook skipped',
                            'Note, it`s only for LOCAL APP_ENV=DEV configuration requirements']);
                    }
                }
            }else{
                //$this->io->caution(['Warning: bad response from telegram server, wrong pair "MODULES_TELEGRAM_BOT_NAME" / "MODULES_TELEGRAM_TOKEN"']);
                $this->io->caution(['Warning: bad response from telegram server: "'.($res['error_code']??'').' '.($res['description']??'').'"']);
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

        $mess = '*php bin/console '.$this->getName().' --init* Проверка отправки сообщения из '.$this->telegram->config['app_name']. ' для администратора.';
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

        $preset = $this->telegram->events;

        $list = $this->telegram->getEvents(true);
        $_changes = ['Table for "'.$this->telegramEventRepository->getTableName().'" update successfuly:'];
        foreach ($preset as $item){
            $name = $item['name'];
            if (isset($list[$name])){
                if ($list[$name]['note']==$item['note'] && json_encode($list[$name]['roles'])==json_encode($item['roles'])) {
                    $_changes[$name] = '"'.$name.'" is up to date';
                }else{
                    $this->telegramEventRepository->updateEventByName($item['name'], $item['note'], $item['roles']);
                    $_changes[$name] = '"'.$name.'" was updated';
                }
            }else{
                $this->telegramEventRepository->newEvent($item['name'], $item['note'], $item['roles']);
                $_changes[$name] = '"'.$name.'" was inserted';
            }
        }

        $this->io->success($_changes);
        yield $all_is_good_go_on_dude;


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 5) Прописка пользователя телеграма
        $all_is_good_go_on_dude=5;

        $users = $this->telegram->getUsers(true); // в этот момент мы уже схоронили себе этого пользователя, так что он на 146% у нас есть

        if (isset($users[$admin_chat_id]) && $users[$admin_chat_id]['superuser']==1){
            $this->io->success(['Telegram admin user already in database and superuser']);
        }else{
            $res = $this->sendMessageToLocalWebhook('/MakeMeGreatAgain');

            if ($res){
                $users_new = $this->telegram->getUsers(true);
                if (isset($users_new[$admin_chat_id]) && $users_new[$admin_chat_id]['superuser']==1){
                    if (isset($users[$admin_chat_id])){
                        $this->io->success(['Table "'.$this->telegramUserRepository->getTableName().'" updated, telegram admin user is superuser now']);
                    }else{
                        $this->io->success(['Table "'.$this->telegramUserRepository->getTableName().'" updated, telegram admin user was inserted succesfully']);
                    }
                    $users = $users_new;
                }else{
                    $this->io->caution(['Warning: telegram admin user insert failed.']);
                    $all_is_good_go_on_dude=false;
                }
            }else{
                $all_is_good_go_on_dude=false;
            }
        }

        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 6) Проверка пользователя маркетплейса С КАКИМ НОМЕРОМ ТЕЛЕФОНА?? Ищем первого попавшегося суперюзера
        $all_is_good_go_on_dude=6;

        if ($users[$admin_chat_id]['id_user']>0){
            $this->io->success(['Table "'.$this->telegramUserRepository->getTableName().'" ok, telegram admin user already linked with user #'.$users[$admin_chat_id]['id_user']]);
        }else{

            $superuser = $this->telegram->findProjectUserBySuperuser();
            if (is_array($superuser)){
                $this->io->success(['Superuser found in table "user" with phone '.$superuser['phone']]);

                $paramaters = array(
                    'message' => array(
                        'contact' => array(
                            'phone_number' => $superuser['phone'],
                            'first_name' => 'MarketplaceAdmin',
                            'user_id'=>$admin_chat_id
                        ),
                    )
                );
                $res = $this->sendMessageToLocalWebhook('/contact', $paramaters);

                if ($res){
                    $users_new = $this->telegram->getUsers(true);
                    if (isset($users_new[$admin_chat_id]) && $users_new[$admin_chat_id]['id_user']==$superuser['id']){
                        $this->io->success(['Table "'.$this->telegramUserRepository->getTableName().'" updated, telegram admin user linked with user #'.$superuser['id']]);
                    }else{
                        $this->io->caution(['Warning: send contact failed.']);
                        $all_is_good_go_on_dude=false;
                    }
                }else{
                    $all_is_good_go_on_dude=false;
                }

            }else{
                $this->io->caution(['Warning: user with role = ROLE_SUPERUSER not found in table "user". Create one! In case of manual use security:hash-password']);
                $all_is_good_go_on_dude=false;
            }
        }


        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// 7) Подписываемся на всё что можно, можно через апи телеграм сделать, можно прям тут ручками
        $all_is_good_go_on_dude=7;


        $list = $this->telegram->getUserEvents($admin_chat_id);


        $__changes = ['Subscribe admin for all events. Table "'.$this->telegramUserEventRepository->getTableName().'" update successfuly:'];
        foreach ($preset as $item){
            $name = $item['name'];
            if (isset($list[$name])){
                if ($list[$name]->isActive()) {
                    $__changes[$name] = '"'.$name.'" already subscribed';
                }else{
                    $this->telegram->setActive($admin_chat_id, $name, 1);
                    $__changes[$name] = '"'.$name.'" subscribe activated';
                }
            }else{
                $this->telegram->newUserEvent($admin_chat_id, $name);
                $__changes[$name] = '"'.$name.'" subscribed successfuly';
            }
        }
        $this->io->success($__changes);

        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// Отправка контакта (привязка телефона к контакту) админа
        $all_is_good_go_on_dude=9;
        $names = [];
        foreach ($preset as $item) {
            $name = $item['name'];
            $this->telegram->event($name, 'Сообщение на событие "'.$name.'"');
            $names[] = $name;
        }

        $this->io->success(['telegram events sended ('.implode(', ', $names).')']);
        yield $all_is_good_go_on_dude;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /// Отправка контакта (привязка телефона к контакту) админа
        $all_is_good_go_on_dude=10;

        $this->io->success(['All done. Congratulation!']);

        $this->io->info(['To test methods of webhook run "--testw"']);
        yield $all_is_good_go_on_dude;

    }


    function sendMessageToLocalWebhook(string $mess, $add_params=[]){
        $admin_chat_id = (int)$this->telegram->config["admin_chat_id"];
        $webhook_url_path = $this->router->generate('jc_telegram_webhook');

        $paramaters = array_merge_recursive(array(
            'update_id' => 1,
            'message' => array(
                'message_id' => 1,
                'from' => array(
                    'id' => $admin_chat_id,
                    'first_name' => 'ProjectAdmin',
                    'username' => 'Projectadmin'
                ),
                'text' => $mess,
                'date' => date("U"),
                'entities' => array()
            )
        ), $add_params);

        $_GET['command_request'] = '1';
        $_GET['token'] = $this->telegram->config['token'];
        $request = Request::create($webhook_url_path, 'POST', array(), array(), array(), array(), json_encode($paramaters));
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        $res = json_decode($response->getContent(), true);
        if ($res && isset($res['result']) && $res['result']=='ok'){
            return $res;
        }else{
            $this->io->caution(['Warning: network connect or internal error of telegram server by query "'.$mess.'"',
                    $this->input->getOption('verbose')?$response->getContent():'Set --verbose for more details.']
            );
            return null;
        }

    }


    /**
     * Выгружаем доступные команды и отправляем их одна за одной без верификации результата
     * @return void
     */
    private function telegramTestWebhookommands(){

        $res = $this->sendMessageToLocalWebhook('/getBotCommands');
        $admin_chat_id = (int)$this->telegram->config["admin_chat_id"];

        if ($res){
            $arr = json_decode($res['responce'], true);
            $this->io->block('Found '.count($arr).' available commands: '.implode(', ', $arr));

            foreach ($arr as $command){
                $this->telegram->sendMessage($admin_chat_id, '/' . $command);
                $act_res = $this->sendMessageToLocalWebhook('/' . $command);
                $this->io->block('/' . $command . ' sended (' . ($act_res&&$act_res['result']=='ok' ? 'success' : 'fail') . ')');
            }

        }
    }

}