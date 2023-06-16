<?php

namespace JustCommunication\SmsAeroBundle\Repository;

use JustCommunication\SmsAeroBundle\Entity\LogSms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JustCommunication\CacheBundle\Trait\CacheTrait;
use Psr\Log\LoggerInterface;

/**
 * @method LogSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogSms[]    findAll()
 * @method LogSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogSmsRepository extends ServiceEntityRepository
{
    use CacheTrait;
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, EntityManagerInterface $em)
    {
        parent::__construct($registry, LogSms::class);
        $this->logger = $logger;
        $this->em = $em;

    }

    /**
     * Выборка количество отправленных смс с одного ip за последние сутки, для определения превышения лимита
     * @param $ip
     * @return false
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSendedMessageCountByIp($ip){

        $statement = $this->em->getConnection()->prepare('SELECT COUNT(*) as cnt FROM '.$this->getTableName().' e WHERE datein>NOW()-interval 1 DAY AND ip="'.$ip.'" AND result_code IN (1,5)');
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();
        return $row['cnt']??false;
    }

    /**
     * Выборка статистики отправленных смс с группировкой по ip за прошедшие сутки
     * @return array
     */
    public function getSendedMessageDayStat(){
        // result_code 1-success_send, 5-stub, 6-error, 9-ban
        $statement = $this->em->getConnection()->prepare('SELECT ip, action, GROUP_CONCAT(distinct phone SEPARATOR ",") AS phones, 
            SUM(if(result_code=1 or result_code=5, 1, 0)) AS count_sended, SUM(if(result_code=9, 1, 0)) AS count_banned 
            FROM '.$this->getTableName().' 
            WHERE datein>NOW()-interval 1 DAY 
            GROUP BY ip, action
            ORDER BY count_banned, count_sended
        ');
        $result = $statement->executeQuery();
        $rows = $result->fetchAllAssociative();

        return $rows;
    }
    
    public function newLog($values): LogSms{

        $record = new LogSms();
        $record->setIdUser($values['id_user'])
            ->setDatein(new \DateTime())
            ->setPhone($values['phone'])
            ->setAction($values['action'])
            ->setCode($values['code'])
            ->setMess($values['mess'])
            ->setTry($values['try'])
            ->setIp($values['ip'])
            ->setSended($values['sended'])
            ->setResult($values['result'])
            ->setResultCode($values['result_code'])

        ;
        $this->em->persist($record);
        $this->em->flush();
        return $record;
    }

    /**
     * Имя таблицы с которой работает репозиторий
     * @return string
     */
    public function getTableName(){
        return $this->getClassMetadata()->getTableName();
    }

}
