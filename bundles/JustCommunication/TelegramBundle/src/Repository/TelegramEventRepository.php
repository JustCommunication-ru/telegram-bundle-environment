<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\TelegramBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Trait\CacheTrait;
use Psr\Log\LoggerInterface;

/**
 * @method TelegramEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramEvent[]    findAll()
 * @method TelegramEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramEventRepository extends ServiceEntityRepository
{
    use CacheTrait;
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em, FuncHelper $funcHelper)
    {
        parent::__construct($registry, TelegramEvent::class);
        $this->logger = $logger;
        $this->em = $em;
        $this->funcHelper = $funcHelper;

    }

    public function getEvents($force = false){

        $callback = function(){
            $rows = $this->em->createQuery('SELECT e FROM JustCommunication\TelegramBundle\Entity\TelegramEvent e')->getArrayResult();
            return $this->funcHelper::array_foreach($rows, array('roles', 'note'), 'name');
        };
        return $this->cached('telegram_event', $callback, $force);
    }

}
