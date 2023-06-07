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
                SELECT u 
                FROM JustCommunication\TelegramBundle\Entity\TelegramUser u
                ')->getArrayResult();
            return $this->ss::array_foreach($rows, array('roles', 'note'), 'name');

            $statement = $this->db->prepare('SELECT tu.user_chat_id, tu.datein, tu.is_bot, tu.first_name, tu.username, tu.language_code, tu.superuser, tu.phone, user.id as  id_user, user.roles 
            FROM telegram_users tu 
            LEFT JOIN user ON user.id=tu.id_user 
            ORDER BY username ASC');
            $result = $statement->executeQuery();
            //$arr = FuncHelper::array_foreach($result->fetchAllAssociative(), true, 'user_chat_id');
            $arr = array();
            foreach($result->fetchAllAssociative() as $row){
                if ($row['id_user']){
                    // Здесь надо user.roles превратить во вмеяемую role
                    $roles = json_decode($row['roles'], true);
                    $row['role']=in_array('ROLE_ADMINISTRATOR', $roles)||in_array('ROLE_SUPERUSER', $roles)
                        ?'Superuser'
                        :(
                        in_array('ROLE_MANAGER', $roles)
                            ?'Manager'
                            :'User'
                        );
                }else{
                    $row['role']=$row['superuser']?'Superuser':'User';
                }
                $arr[$row['user_chat_id']]=$row;
            }

        };
        return $this->cached('telegram_event', $callback, $force);
    }

}
