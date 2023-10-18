<?php

namespace JustCommunication\TelegramBundle\Controller;

use JustCommunication\TelegramBundle\Service\TelegramHelper;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Контролер по сути нужен только для webhook-а
 * Вся логика общения с пользователем прям тут, работа с телеграм через сервис TelegramHelper
 * @todo Логику общения с пользователем надо тоже вынести в вебхук, тут оставить только передачу параметров и вывод ответа
 * @todo *Comand методы должны возвращать строго строку
 * @todo WebhookInterface придумать
 * @todo сделать таблицу emoji констант
 */
class TelegramController extends AbstractController
{
    private $webhook;
    private TelegramHelper $telegram;

    // вместо autowire TelegramWebhook $webhook используется явное подключение вебхука через конфиги
    // для того чтобы можно было переопределить повидение телеграм бота
    public function __construct($webhook){
        $this->response = new Response();
        $this->response->headers->set('Content-Type','application/json');
        $this->webhook = $webhook;
    }

    /**
     * Тот самый WebHook (точка доступа) за которую будет дергать Телеграм когда надо будет реагировать на пользователя в чате
     * @param TelegramHelper $telegram
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/telegram/webhook', name: 'jc_telegram_webhook')]
    //public function telegram_webhook(TelegramHelper $telegram, Request $request, ParameterBagInterface $services_params){
    public function telegram_webhook(TelegramHelper $telegram, Request $request): JsonResponse{
        $this->telegram = $telegram;
        $webhook = $this->webhook;

        // Аутентификация у нас по токену в get параметре
        $token = $_GET['token'] ?? '';

        // Если мы обратимся через Request()
        // Webhook имеет режим имитации пользовательского обращения,
        // Изначально реализовывался для крон методов
        // @2022-08-10 я тут только что понял, что это бэкдор с помощью
        // которого можно от лица любого пользователя выполнять действия, надо защитить этот метод

        if (isset($_GET['command_request'])) {
            $json = $request->getContent();
            $command_request_mode = true;
        }else{
            // содержимое запроса лежит в виде json в потоке
            $json = file_get_contents('php://input');
            $command_request_mode = false;
        }

        $this->log("\r\n"."##################################################################"."\r\n".'NEW MESSAGE:'."\r\n".$json);

        $send_result = array('not_sended'); // результат отправки ответа пользователю
        $result = []; // array (will be json encode) result of webhook work
        //$text =  ''; // message text to user
        if ($token == $telegram->config['token']) {

            try {
                $arr = json_decode($json, true);


                // Стандартные сообщения имеют update_id и message
                if (isset($arr['message'])) {

                    $user_chat_id = isset($arr['message']['from']['id']) ? $arr['message']['from']['id'] : 0;
                    $mess = isset($arr['message']['text']) ? trim($arr['message']['text']) : '';

                    // хак на присланные контакты
                    if (isset($arr['message']['contact'])) {
                        // Если все необходимые параметры на месте, передадим все значения в качестве опций
                        if (isset($arr['message']['contact']['phone_number']) &&
                            isset($arr['message']['contact']['first_name']) &&
                            isset($arr['message']['contact']['user_id'])) {
                            $mess = '/contact ' . $arr['message']['contact']['user_id'] . ' ' . $arr['message']['contact']['phone_number'] . ' ' . $arr['message']['contact']['first_name'];
                        } else {
                            $mess = '/contact'; // выплняем пустую команду
                        }
                        // Если нам прислали контакт, то притворяемся что выполняем команду /contact
                    }
                } else {
                    // При нажатии на кнопку мы получаем update_id и callback_query в котором содержимое-аналог message
                    // и плюс полный объект message в котором был кнопка
                    if (isset($arr['callback_query'])) {
                        $user_chat_id = isset($arr['callback_query']['from']['id']) ? $arr['callback_query']['from']['id'] : 0;
                        $mess = isset($arr['callback_query']['data']) ? trim($arr['callback_query']['data']) : '';

                    } else {
                        $mess = null;
                        $user_chat_id = null;
                    }
                }
                // mess это текст, а message это уже объект, а не уй собачий
                $message = isset($arr['message']) ? $arr['message'] : (isset($arr['callback_query']['message']) ? $arr['callback_query']['message'] : false);


                if ($mess){
                    $webhook->setMess($mess);

                    if ($user_chat_id > 0) { // формальность, чат id всегда определен

                        $telegram->saveMessage($message, $arr['update_id']); // сохраняем входящее сообщение а так же инормацию о пользователе
                        $telegram->checkUser($message); // сохраняем входящее сообщение а так же инормацию о пользователе

                        $list = $telegram->getEvents(); //подписки

                        $users = $telegram->getUsers(); // в этот момент мы уже схоронили себе этого пользователя, так что он на 146% у нас есть

                        // Тем не менее предохраняемся, $user_chat_id и chat_id одно и то же
                        $user = $users[$user_chat_id] ?? array('first_name' => '-none-', 'superuser' => 0, 'user_chat_id' => $user_chat_id, 'role' => 'User');
                        $user['role'] = $user['role'] != '' ? mb_convert_case($user['role'], MB_CASE_TITLE) : ($user['superuser'] ? 'Superuser' : 'User');

                        $webhook->setUser($user);


                        $ans = []; // mixed (array or string)
                        // Агримся на команды от пользователя
                        if (str_starts_with($mess, '/')) {

                            // 2021-11-24 Нововведение, бьем по пробелам. Первый токен - команда, остальные - параметры
                            if (strpos($mess, " ") > 0) {
                                $arr = explode(" ", str_replace("/", "", $mess));
                                $command = array_shift($arr);
                                $params = $arr;
                            } else {
                                $command = str_replace("/", "", $mess);
                                $params = array();
                            }

                            // А как сделать так что бы роль манагера вберала в себя функционал юзера?

                            if (str_starts_with($command, 'add')) {
                                $_act = 'add';
                            } elseif (str_starts_with($command, 'remove')) {
                                $_act = 'remove';
                            } elseif (str_starts_with($command, 'send')) {
                                $_act = 'send';
                            } else {
                                $_act = '';
                            }
                            $_subscription = $_act != '' ? substr($command, strlen($_act)) : '';


                            if ($command == 'b') {

                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'Экспериментальное меню',
                                    //'reply_markup'=>['inline_keyboard'=>[[['text'=>'шляпа', 'callback_data'=>'/start']]]]

                                    //'reply_markup'=> json_encode(['remove_keyboard'=>true])

                                    'reply_markup' => json_encode([
                                        'inline_keyboard' => [
                                            [['text' => 'Кто я', 'callback_data' => '/whoami'], ['text' => 'Мои подписки', 'callback_data' => '/getMyList']],
                                            [['text' => 'Список пользователей', 'callback_data' => '/getUsers'], ['text' => 'Стартуй', 'callback_data' => '/start'], ['text' => 'Помощь', 'callback_data' => '/help']],
                                        ],
                                        //'resize_keyboard'=>true
                                    ])
                                ]);
                                $ans = false;
                            } elseif ($command == 'b2') {
                                // Вот так можно попросить свой телефон у пользователя
                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'давай дружить' . "11",
                                    'reply_markup' => json_encode([
                                        'keyboard' => [
                                            //[[]],
                                            [[
                                                'request_contact' => true,
                                                'text' => 'Пройти идентификацию'
                                            ]],
                                            [[
                                                'text' => 'Отказаться'
                                            ]],
                                            //[[]]
                                        ],
                                        //'one_time_keyboard'=>true,
                                        'resize_keyboard' => true
                                    ])
                                ]);
                                $ans = false;
                            } elseif ($command == 'c') {
                                // Вот так за собой удалить кастомную клаву
                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'Кастомная клавиатура удалена',
                                    'reply_markup' => json_encode(['remove_keyboard' => true])
                                ]);
                                $ans = false;
                            } elseif ($command == 'b4') {
                                // Спросить и заменить ответ
                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'Некое событие',
                                    'reply_markup' => json_encode([
                                        'inline_keyboard' => [
                                            [['text' => 'Согласен', 'callback_data' => '/yes'], ['text' => 'Против', 'callback_data' => '/no']],
                                        ],
                                    ])
                                ]);
                                $ans = false;
                            } elseif ($command == 'yes') {
                                // Спросить и заменить ответ
                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'Вы подтвердили свое согласие!',
                                    'message_id' => $message['message_id'] ?? 0
                                ], 'editMessageText');
                                $ans = false;
                            } elseif ($command == 'no') {
                                // Спросить и заменить ответ
                                $send_result = $telegram->sendMess($user_chat_id, [
                                    'text' => 'Вы отказались от всего-всего',
                                    'message_id' => $message['message_id'] ?? 0
                                    //'reply_to_message_id'=>$message['message_id']??0
                                ], 'editMessageText');
                                $ans = false;
                            } else {
                                if (method_exists($webhook::class, $command . $user['role'] . 'Command')) {
                                    $method = $command . $user['role'] . 'Command';
                                    $ans = $webhook->$method($params);
                                } elseif ($_act == 'add' && str_starts_with($command, $_act) && array_key_exists($_subscription, $list)) {
                                    //$row = $telegram->getUserSubscribe($user_chat_id, $_subscription);
                                    $row = $telegram->getUserEvent($user_chat_id, $_subscription);
                                    if (is_array($row)) {
                                        if ($row['active'] == 0) {
                                            // тогда включаем подписку снова
                                            $telegram->setActive($user_chat_id, $_subscription, 1);
                                            $ans = '"' . $list[$_subscription]['note'] . '" успешно оформлена, снова.';
                                        } else {
                                            $ans = '"' . $list[$_subscription]['note'] . '" уже подключена.';
                                        }
                                    } else {
                                        $telegram->newUserEvent($user_chat_id, $_subscription);
                                        $ans = '"' . $list[$_subscription]['note'] . '" успешно оформлена.';
                                    }

                                } elseif ($_act == 'remove' && str_starts_with($command, $_act) && array_key_exists(substr($command, strlen($_act)), $list)) {
                                    $row = $telegram->getUserEvent($user_chat_id, $_subscription);
                                    if (is_array($row) && $row['active'] == 1) {
                                        //$this->db->delete('telegram_users_list', 'user_chat_id='.$user_chat_id.' AND name="'.str_replace('stop', 'start', $command).'"');
                                        $telegram->setActive($user_chat_id, $_subscription, 0);
                                        $ans = '"' . $list[$_subscription]['note'] . '" успешно отменена.';
                                    } else {
                                        $ans = '"' . $list[$_subscription]['note'] . '" не была подключена.';
                                    }
                                } elseif ($_act == 'send' && str_starts_with($command, $_act) && array_key_exists(substr($command, strlen($_act)), $list)) {
                                    $method = 'send' . mb_convert_case($_subscription, MB_CASE_TITLE) . 'Action';
                                    if (method_exists($this, $method)) {
                                        //sendReportAction()
                                        $ans = $this->$method(false);
                                    } else {
                                        $ans = 'Отчет не может быть доставлен, отсутствует обработчик';
                                    }
                                } else {
                                    $ans = $webhook->commandNotFound($command, $user['role']);
                                }
                            }

                        } else {
                            $ans = $webhook->justTextResponse($mess);
                        }

                        if ($command_request_mode) {
    //                        $send_result = $telegram->sendMessage($user_chat_id, '*commad_request*:'."\r\n".$mess);
                        }


                        if ($ans) {
                            // Разбираемся с ответом. Простой текст или структура
                            if (is_array($ans)) {
                                $text = $ans['text'] ?? '';
                            } else {
                                $text = $ans;
                                $ans = [
                                    'text' => $text
                                ];
                            }


                            $this->log('RESULT ANSWER:' . "-end-" . json_encode($ans));

                            //['text'=> $text.($webhook->remove_keyboard?"X":"Y"), 'reply_markup'=>($webhook->remove_keyboard?json_encode(['remove_keyboard'=>true]):'[]')]
                            //$send_result = $telegram->sendMessage($user_chat_id, $text);

                            $send_result = $telegram->sendMess($user_chat_id, $ans);


                        } else {
                            $text = '';
                        }


                        $this->log(":result:");
                        $this->log($send_result);


                        $result = array("result" => "ok", "request" => $mess, "responce" => $text);
                    } else {
                        // Нет пользователя этого прям не может быть,
                        // то есть боту не должен приходить запрос от неидентифицированных источников, игнорим
                        $result = array("result" => "request error: no_user");
                    }
                }else{
                    $result = array("result" => "no_mess, error incoming structure");
                }

            }catch(\Throwable $e){
                $this->log("EXCEPTION:");
                $this->log($e->getMessage().' in '.$e->getFile().' on '.$e->getLine());
                $result = array('result' => "fail", "message" => $e->getMessage().' in '.$e->getFile().' on '.$e->getLine());
            }
        }else{
            $result =  array('result'=>'error token');
        }

        $this->log("\r\n".'END'."\r\n"."##################################################################"."\r\n"."\r\n"."\r\n");

        // Логи рабочие. отключим пока
        //$application_path = $this->getParameter('kernel.project_dir');
        //file_put_contents($application_path . '/var/log/' . 'webhook' . date('Y.m.d.H.i.s') . '.txt', 'token:' . $token . "\r\n" . 'receive:' . $json . "\r\n" . 'result:' . print_r($result, true). "\r\n" . 'text:' ."\r\n-----------------------------------\r\n". $text. "\r\n-----------------------------------\r\n" . 'send_result:' . print_r($send_result, true)."\r\n------------\r\n".print_r($users??array(), true)."\r\n------------\r\n".print_r($user??array(), true));

        return $this->json($result);

    }

    private function log($mixed){
        if ($this->telegram->config['webhook_logging_turn_on']) {
            $application_path = $this->getParameter('kernel.project_dir');
            file_put_contents($application_path . "/var/log/telegram.txt", (is_array($mixed) || is_object($mixed) ? var_export($mixed, true) : $mixed) . "\r\n", FILE_APPEND);
        }
    }

}


