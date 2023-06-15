<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramUser;
use JustCommunication\CacheBundle\Trait\CacheTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method TelegramUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramUser[]    findAll()
 * @method TelegramUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramUserRepository extends ServiceEntityRepository
{
    use CacheTrait;
    private EntityManagerInterface $em;
    const CACHE_NAME = 'telegram_users';

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct($registry, TelegramUser::class);
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Пока как массив массивов, потом можно будет строить массив сущностей
     * @param $force
     * @return mixed
     */
    public function getUsers($force = false){
        $callback = function(){

            $rows = $this->em->createQuery('
                SELECT tu.id, tu.datein, tu.userChatId as user_chat_id, tu.firstName as first_name, tu.username, tu.superuser, tu.phone, tu.idUser as id_user, 
                u.roles
                FROM JustCommunication\TelegramBundle\Entity\TelegramUser tu
                LEFT JOIN App\Entity\User u WITH tu.idUser=u.id
                ')->getArrayResult();
            //return $this->ss::array_foreach($rows, true, 'user_chat_id');
            //return $rows;

            $arr = array();
            foreach($rows as $row){
                if ($row['id_user']){
                    // Здесь надо user.roles превратить во вмеяемую role

                    $row['role']=in_array('ROLE_ADMINISTRATOR', $row['roles'])||in_array('ROLE_SUPERUSER', $row['roles'])
                        ?'Superuser'
                        :(
                        in_array('ROLE_MANAGER', $row['roles'])
                            ?'Manager'
                            :'User'
                        );
                }else{
                    $row['role']=$row['superuser']?'Superuser':'User';
                }
                $arr[$row['user_chat_id']]=$row;
            }
            return $arr;
        };
        return $this->cached(self::CACHE_NAME, $callback, $force);
    }


    /**
     * Проверка отправителя сообщения, если такого пользователя еще не было - добавляем, иначе обновляем при необходимости
     * @param $message
     * @return TelegramUser|null
     */
    public function checkUser($message){
        $users = $this->getUsers();
        if (!array_key_exists($message['from']['id'], $users)) {
            $user = $this->addUser($message['from']);
        }elseif (
            (isset($message['from']['first_name']) && $message['from']['first_name']!='' && ($users[$message['from']['id']]['first_name']==''||$users[$message['from']['id']]['first_name']=='-') )
            ||
            (isset($message['from']['username']) && $message['from']['username']!='' && ($users[$message['from']['id']]['username']==''|| $users[$message['from']['id']]['username']=='-'))){
            // Небольшой хак на случай, если у нас не было инфы о пользователе (например его ручками добавили)
            $user = $this->updateUser($message['from']);
        }else{
            $user = $users[$message['from']['id']];
        }
        return $user;
    }


    /**
     * Добавление нового пользователя
     * @param $arr
     */
    public function addUser($arr){ //message.from
        if (isset($arr['id'])&& $arr['id']>0){
            $arr['id'] = (int)$arr['id'];
            // Так как мы не гарантируем что запрос на вставку не повторный сначала спросим базку
            $user = $this->findOneBy(['userChatId'=>$arr['id']]);

            if (is_null($user)){
                $user = new TelegramUser();
                $user->setUserChatId($arr['id'])
                    ->setDatein(new \DateTime())
                    ->setIsBot(isset($arr['is_bot'])&&$arr['is_bot']?true:false)
                    ->setFirstName($arr['first_name'] ?? '-')
                    ->setUsername($arr['username'] ?? '-')
                    ->setLanguageCode($arr['language_code'] ?? '')
                    ->setPhone($arr['phone'] ?? '')
                    ->setSuperuser(false)
                ;
                $this->em->persist($user);
                $this->em->flush();
            }
            // сбросим в любом сучае. раз нас сюда послали, значит в кэше нет записи.
            $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
            return $user;
        }else{
            return null;
        }
    }

    /**
     * Обновление информации о пользователе
     * @param $arr
     */
    public function updateUser($arr){//message.from
        if (isset($arr['id'])&& $arr['id']>0){
            $arr['id'] = (int)$arr['id'];

            $user = $this->findOneBy(['userChatId'=>$arr['id']]);

            if (!is_null($user)){
                $user->setFirstName($arr['first_name'] ?? '-')
                    ->setUsername($arr['username'] ?? '-');
                $this->em->persist($user);
                $this->em->flush();

            }else{
                // то что? ошибка?
            }

            // обновился один юзер, а сбрасывать весь список
            $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        }else{
            $user=null;
        }
        return $user;
    }

    /**
     * Сделать пользователя суперюзером
     * @param $user_chat_id
     * @return TelegramUser|null
     */
    public function setSuperuser($user_chat_id){

        $user = $this->findOneBy(['userChatId'=>$user_chat_id]);

        if (!is_null($user)){
            $user->setSuperuser(true);
            $this->em->persist($user);
            $this->em->flush();
        }else{
            // то что? ошибка?
        }
        $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        return $user;
    }

    /**
     * Позволяем пользователю самому притворятся любым телефоном
     * Метод позволяет вставить любую дичь, так что все проверки, пожалуйста, на стороне.
     * Этим же методом затирать телефон.
     * Единственно на всякий случай режем по длине, чтобы не допустить mysql ошибку
     * @param $user_chat_id
     * @param $phone
     */
    public function setUserPhone($user_chat_id, $phone){

        $user = $this->findOneBy(['userChatId'=>$user_chat_id]);

        if (!is_null($user)){
            $user->setPhone($phone);
            $this->em->persist($user);
            $this->em->flush();
        }else{
            // то что? ошибка?
        }
        $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        return $user;
    }

    /**
     * Привязка пользователя системы к пользователю телеграм
     * @param $user_chat_id
     * @param $id_user
     * @param $phone
     * @return TelegramUser|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function linkUser($user_chat_id, $id_user, $phone=''){
        $user = $this->findOneBy(['userChatId'=>$user_chat_id]);

        if (!is_null($user)){
            $user->setIdUser($id_user);
            if ($phone!='') {
                $user->setPhone($phone);
            }
            $this->em->persist($user);
            $this->em->flush();
        }else{
            // то что? ошибка?
        }
        $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        return $user;
    }


    public function findProjectUserByPhone($tel){

        $row = $this->em->createQuery('
            SELECT u FROM App\Entity\User u
            WHERE u.phone=:phone
            ')->setParameter('phone', $tel)
            ->getOneOrNullResult();
        return $row;
    }

    /**
     * Специально для jc:telegram --init
     * @return array|false
     * @throws \Doctrine\DBAL\Exception
     */
    public function findProjectUserBySuperuser(){
        //
        $tableName = $this->em->getClassMetadata('App\Entity\User')->getTableName();
        // createQuery ругается на JSON_CONTAINS, поэтому на чистом sql
        $statement = $this->em->getConnection()->prepare('SELECT *  FROM '.$tableName.' WHERE JSON_CONTAINS(roles,\'"ROLE_SUPERUSER"\',"$")=1 ORDER BY id ASC LIMIT 1');
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();

        return $row;
    }

    /**
     * Имя таблицы с которой работает репозиторий
     * @return string
     */
    public function getTableName(){
        return $this->getClassMetadata()->getTableName();
    }
}
