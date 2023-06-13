<?php

namespace JustCommunication\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TelegramMessages
 *
 * @ORM\Table(name="telegram_message", indexes={@ORM\Index(name="user_chat_id", columns={"user_chat_id"}), @ORM\Index(name="datein", columns={"datein"})})
 * @ORM\Entity
 */
class TelegramMessage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="update_id", type="integer", nullable=false)
     */
    private $updateId;

    /**
     * @var int
     *
     * @ORM\Column(name="message_id", type="integer", nullable=false)
     */
    private $messageId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_chat_id", type="bigint", nullable=false)
     */
    private int $userChatId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    //private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datein", type="datetime", nullable=false)
     */
    private $datein;

    /**
     * @var string
     *
     * @ORM\Column(name="mess", type="text", length=65535, nullable=false)
     */
    private $mess;

    /**
     * @var string
     *
     * @ORM\Column(name="entities", type="text", length=65535, nullable=false, options={"comment"="json"})
     */
    private $entities;

    //------------------------------------------------------------------------------------------------------------------

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpdateId(): int
    {
        return $this->updateId;
    }

    /**
     * @param int $updateId
     */
    public function setUpdateId(int $updateId): self
    {
        $this->updateId = $updateId;
        return $this;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->messageId;
    }

    /**
     * @param int $messageId
     */
    public function setMessageId(int $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserChatId(): int
    {
        return $this->userChatId;
    }

    /**
     * @param int $userChatId
     */
    public function setUserChatId(int $userChatId): self
    {
        $this->userChatId = $userChatId;
        return $this;
    }

    /**
     * @return \DateTime
     */
//    public function getDate(): \DateTime
//    {
//        return $this->date;
//    }

    /**
     * @param \DateTime $date
     */
//    public function setDate(\DateTime $date): self
//    {
//        $this->date = $date;
//        return $this;
//    }

    /**
     * @return \DateTime
     */
    public function getDatein(): \DateTime
    {
        return $this->datein;
    }

    /**
     * @param \DateTime $datein
     */
    public function setDatein(\DateTime $datein): self
    {
        $this->datein = $datein;
        return $this;
    }

    /**
     * @return string
     */
    public function getMess(): string
    {
        return $this->mess;
    }

    /**
     * @param string $mess
     */
    public function setMess(string $mess): self
    {
        $this->mess = $mess;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntities(): string
    {
        return $this->entities;
    }

    /**
     * @param string $entities
     */
    public function setEntities(string $entities): self
    {
        $this->entities = $entities;
        return $this;
    }


}
