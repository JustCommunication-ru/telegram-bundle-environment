<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\FuncBundle\Service\FuncHelper;
use JustCommunication\TelegramBundle\Entity\TelegramEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\CacheBundle\Trait\CacheTrait;
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
    const CACHE_NAME = 'telegram_events';

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct($registry, TelegramEvent::class);
        $this->logger = $logger;
        $this->em = $em;

    }

    public function getEvents($force = false){

        $callback = function(){
            $rows = $this->em->createQuery('SELECT e FROM JustCommunication\TelegramBundle\Entity\TelegramEvent e')->getArrayResult();
            return FuncHelper::array_foreach($rows, array('roles', 'note'), 'name');
        };
        return $this->cached(self::CACHE_NAME, $callback, $force);
    }

    public function updateEventByName($name, $note, $roles): TelegramEvent{
        $event = $this->findOneBy(['name'=>$name]);
        if (!is_null($event)){
            $event->setNote($note)->setRoles($roles);
            $this->em->persist($event);
            $this->em->flush();
        }else{
            //error
        }
        $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        return $event;
    }

    public function newEvent($name, $note, $roles): TelegramEvent{
        $event = new TelegramEvent();
        $event->setName($name, $note, $roles)->setNote($note)->setRoles($roles);
        $this->em->persist($event);
        $this->em->flush();
        $this->cacheHelper->getCache()->delete(self::CACHE_NAME);
        return $event;
    }

    /**
     * Имя таблицы с которой работает репозиторий
     * @return string
     */
    public function getTableName(){
        return $this->getClassMetadata()->getTableName();
    }

}
