<?php

namespace JustCommunication\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TelegramUsersEvents
 * Подписки пользователей
 * @ORM\Table(name="telegram_user_event", indexes={@ORM\Index(name="user_chat_id", columns={"user_chat_id"}), @ORM\Index(name="code", columns={"name"})}, uniqueConstraints={@ORM\UniqueConstraint(name="user_event", columns={"user_chat_id", "name"})})
 * @ORM\Entity
 */
class TelegramUserEvent
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
     * @var \DateTime
     *
     * @ORM\Column(name="datein", type="datetime", nullable=false)
     */
    private $datein;

    /**
     * @var int
     *
     * @ORM\Column(name="user_chat_id", type="bigint", nullable=false)
     */
    private int $userChatId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20, nullable=false)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }


}
