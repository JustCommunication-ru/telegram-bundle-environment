<?php

namespace JustCommunication\SmsAeroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogSms
 *
 * @ORM\Table(name="log_sms", indexes={@ORM\Index(name="phone", columns={"phone"}), @ORM\Index(name="datein", columns={"datein"}), @ORM\Index(name="ip", columns={"ip"}), @ORM\Index(name="result_code", columns={"result_code"})})
 * @ORM\Entity
 */
class LogSms
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
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datein", type="datetime", nullable=false)
     */
    private $datein;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=12, nullable=false)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=20, nullable=false)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="mess", type="string", length=255, nullable=false)
     */
    private $mess;

    /**
     * @var bool
     *
     * @ORM\Column(name="try", type="boolean", nullable=false)
     */
    private $try;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50, nullable=false)
     */
    private $ip;

    /**
     * @var bool
     *
     * @ORM\Column(name="sended", type="boolean", nullable=false)
     */
    private $sended;

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="text", length=65535, nullable=false)
     */
    private $result;


    /**
     * Код результата, чтобы понять что случилось (1- успешная отправка, 5- stub/фиктивная отправка, 6 - ошибка, 9 - бан по огранчению
     * @var int
     *
     * @ORM\Column(name="result_code", type="integer", nullable=false)
     */
    private $resultCode;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return LogSms
     */
    public function setId(int $id): self
    {
        $this->id = $id;
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
     * @return LogSms
     */
    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;
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
     * @return LogSms
     */
    public function setDatein(\DateTime $datein): self
    {
        $this->datein = $datein;
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
     * @return LogSms
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return LogSms
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return LogSms
     */
    public function setCode(string $code): self
    {
        $this->code = $code;
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
     * @return LogSms
     */
    public function setMess(string $mess): self
    {
        $this->mess = $mess;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTry(): bool
    {
        return $this->try;
    }

    /**
     * @param bool $try
     * @return LogSms
     */
    public function setTry(bool $try): self
    {
        $this->try = $try;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return LogSms
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSended(): bool
    {
        return $this->sended;
    }

    /**
     * @param bool $sended
     * @return LogSms
     */
    public function setSended(bool $sended): self
    {
        $this->sended = $sended;
        return $this;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * @param string $result
     * @return LogSms
     */
    public function setResult(string $result): self
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return int
     */
    public function getResultCode(): int
    {
        return $this->resultCode;
    }

    /**
     * @param int $resultCode
     * @return LogSms
     */
    public function setResultCode(int $resultCode): self
    {
        $this->resultCode = $resultCode;
        return $this;
    }


}
