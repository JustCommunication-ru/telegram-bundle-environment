<?php

namespace JustCommunication\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TelegramUsers
 * Пользователи канала которые проявили хоть какую-нибудь активность
 * @ORM\Table(name="telegram_user", indexes={@ORM\Index(name="chats", columns={"user_chat_id"})})
 * @ORM\Entity
 */
class TelegramUser
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
     * @var bool
     *
     * @ORM\Column(name="is_bot", type="boolean", nullable=false)
     */
    private $isBot;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=30, nullable=false)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=30, nullable=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="language_code", type="string", length=2, nullable=false)
     */
    private $languageCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="superuser", type="boolean", nullable=false)
     */
    private $superuser;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=12, nullable=false)
     */
    private $phone = '';

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="bigint", nullable=true)
     */

    private int $idUser;

    //----------------------------------------------------------------------------------------------

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return TelegramUser
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
     * @return TelegramUser
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
     * @return TelegramUser
     */
    public function setUserChatId(int $userChatId): self
    {
        $this->userChatId = $userChatId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBot(): bool
    {
        return $this->isBot;
    }

    /**
     * @param bool $isBot
     * @return TelegramUser
     */
    public function setIsBot(bool $isBot): self
    {
        $this->isBot = $isBot;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return TelegramUser
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return TelegramUser
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * @param string $languageCode
     * @return TelegramUser
     */
    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuperuser(): bool
    {
        return $this->superuser;
    }

    /**
     * @param bool $superuser
     * @return TelegramUser
     */
    public function setSuperuser(bool $superuser): self
    {
        $this->superuser = $superuser;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser(): int
    {
        return $this->idUser;
    }

    /**
     * @param int $idUser
     * @return self
     */
    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return TelegramUser
     */
    public function setPhone(string $phone): self
    {
        $this->phone = substr($phone, 0, 12);
        return $this;
    }

}
