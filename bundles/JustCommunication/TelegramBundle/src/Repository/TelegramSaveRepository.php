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
}
