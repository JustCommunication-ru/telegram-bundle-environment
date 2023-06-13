<?php

namespace JustCommunication\TelegramBundle\Repository;

use JustCommunication\TelegramBundle\Entity\TelegramSave;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method TelegramSave|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramSave|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramSave[]    findAll()
 * @method TelegramSave[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramSaveRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry,LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct($registry, TelegramSave::class);
        $this->logger = $logger;
        $this->em = $em;
    }

    public function updateMess(TelegramSave $teleSave, $mess): TelegramSave{
        $teleSave->setMess($mess);
        $this->em->persist($teleSave);
        $this->em->flush();
        return $teleSave;
    }

    public function newSave(string $ident, int $chat_id, int $message_id, $mess): TelegramSave{
        $teleSave = new TelegramSave();
        $teleSave->setDatein(new \DateTime)
            ->setIdent($ident)
            ->setUserChatId($chat_id)
            ->setMessageId($message_id)
            ->setMess($mess);
        //->setMess($ans['result']['text']);
        $this->em->persist($teleSave);
        $this->em->flush();
        return $teleSave;
    }

    /**
     * Имя таблицы с которой работает репозиторий
     * @return string
     */
    public function getTableName(){
        return $this->getClassMetadata()->getTableName();
    }
}
