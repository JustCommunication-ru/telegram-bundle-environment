<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramUserEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\TelegramBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Trait\CacheTrait;
use Psr\Log\LoggerInterface;

/**
 * @method TelegramUserEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramUserEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramUserEvent[]    findAll()
 * @method TelegramUserEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramUserEventRepository extends ServiceEntityRepository
{
    use CacheTrait;
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em, FuncHelper $funcHelper)
    {
        parent::__construct($registry, TelegramUserEvent::class);
        $this->logger = $logger;
        $this->em = $em;
        $this->funcHelper = $funcHelper;

    }

    public function getUserEvent($user_chat_id, $event_name){
        $rows = $this->em->createQuery('
            SELECT ue FROM JustCommunication\TelegramBundle\Entity\TelegramUserEvent ue
            WHERE ue.userChatId=:userChatId AND ue.name=:event_name
            ')
            ->setParameter('userChatId', $user_chat_id)
            ->setParameter('event_name', $event_name)
            ->getArrayResult();
        return $rows;
    }

    public function getUserEvents($user_chat_id){
        $rows = $this->em->createQuery('
            SELECT ue FROM JustCommunication\TelegramBundle\Entity\TelegramUserEvent ue
            WHERE ue.userChatId=:userChatId AND ue.active=1
            ')
            ->setParameter('userChatId', $user_chat_id)
            ->getArrayResult();
        return $rows;
    }

    public function setActive($user_chat_id, $event_name, $active){
        $res = $this->em->createQuery('
            UPDATE JustCommunication\TelegramBundle\Entity\TelegramUserEvent ue
            SET ue.active=:active
            WHERE ue.userChatId=:userChatId AND ue.name=:event_name
            ')
            ->setParameter('userChatId', $user_chat_id)
            ->setParameter('event_name', $event_name)
            ->setParameter('active', $active)
            ->execute();
        return $res;
    }


}
