<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\CacheBundle\Trait\CacheTrait;
use Psr\Log\LoggerInterface;

/**
 * @method TelegramMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramMessage[]    findAll()
 * @method TelegramMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramMessageRepository extends ServiceEntityRepository
{
    use CacheTrait;
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct($registry, TelegramMessage::class);
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Сохраняем входящее сообщение
     *
     * @param $arr
     */
    public function newMessage($message, $update_id){


        if ($message) {
            $telegramMessage = new TelegramMessage();
            $telegramMessage->setUpdateId($update_id)
                ->setMessageId($message['message_id'])
                ->setUserChatId($message['from']['id'])
                ->setDatein(new \DateTime(date("Y-m-d H:i:s", 1649120903)))
                ->setMess(($message['text'] ?? '') . (isset($message['contact']) ? json_encode($message['contact']) : ''))
                ->setEntities(isset($message['entities']) ? json_encode($message['entities']) : '')
            ;
            $this->em->persist($telegramMessage);
            $this->em->flush();
        }
    }

    /**
     * Имя таблицы с которой работает репозиторий
     * @return string
     */
    public function getTableName(){
        return $this->getClassMetadata()->getTableName();
    }

}
