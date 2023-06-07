<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\TelegramBundle\Service\SSHelper;
use JustCommunication\TelegramBundle\Trait\CacheTrait;
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

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em, SSHelper $SSHelper)
    {
        parent::__construct($registry, TelegramUser::class);
        $this->logger = $logger;
        $this->em = $em;
        $this->ss = $SSHelper;

    }

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
        return $this->cached('telegram_event', $callback, $force);
    }

}
