<?php

namespace JustCommunication\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TelegramMessages
 * Сохраненные сообщения, для того чтобы можно было их редактировать (хотя наверняка есть метод выборки, но разработчик пошел другим путем)
 * @ORM\Table(name="telegram_save", indexes={@ORM\Index(name="ident", columns={"ident"}), @ORM\Index(name="datein", columns={"datein"})})
 * @ORM\Entity
 */
class TelegramSave
{
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

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
     * @var string
     *
     * @ORM\Column(name="ident", type="string", length=30, nullable=false)
     */
    private $ident;

    /**
     * @var int
     *
     * @ORM\Column(name="user_chat_id", type="bigint", nullable=false)
     */
    private int $userChatId;

    /**
     * @var int
     *
     * @ORM\Column(name="message_id", type="integer", nullable=false)
     */
    private $messageId;

    /**
     * @var string
     *
     * @ORM\Column(name="mess", type="text", length=65535, nullable=false)
     */
    private $mess;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param int $id
     * @return self
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
     * @return self
     */
    public function setDatein(\DateTime $datein): self
    {
        $this->datein = $datein;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdent(): string
    {
        return $this->ident;
    }

    /**
     * @param string $ident
     * @return self
     */
    public function setIdent(string $ident): self
    {
        $this->ident = $ident;
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
     * @return self
     */
    public function setUserChatId(int $userChatId): self
    {
        $this->userChatId = $userChatId;
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
     * @return self
     */
    public function setMessageId(int $messageId): self
    {
        $this->messageId = $messageId;
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
     * @return self
     */
    public function setMess(string $mess): self
    {
        $this->mess = $mess;
        return $this;
    }

}
