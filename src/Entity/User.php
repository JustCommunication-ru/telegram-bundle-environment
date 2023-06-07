<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"}), @ORM\UniqueConstraint(name="phone", columns={"phone"})})
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface,PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="bigint", nullable=false)
     *
     *
     */
    private $id;

    /**
     * @ORM\Column(name="email", type="string", length=180, unique=true, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(name="roles", type="json", nullable=false)
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @ORM\Column(name="phone", type="string", length=20, nullable=false, unique=true)
     */
    private $phone;

    /**
     * дата регистрации
     * @ORM\Column(name="datein", type="datetime", nullable=false)
     */
    private $datein;

    /**
     * дата входа
     * @ORM\Column(name="dateen", type="datetime", nullable=true)
     */
    private $dateen;

    /**
     * дата привязки телефона
     * @ORM\Column(name="dateph", type="datetime", nullable=true)
     */
    private $dateph;

    /**
     * проект с которого зарегистрирован пользователь
     * @ORM\Column(name="source", type="string", length=50, nullable=false)
     */
    private $source;


    /**
     * @var int
     *
     * @ORM\Column(name="jcbank_ident", type="integer", nullable=true)
     */
    private $jcbankIdent=0;

    //--------------------------------------------------------------------------------------

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->phone;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getDatein(): ?\DateTimeInterface
    {
        return $this->datein;
    }

    public function setDatein(\DateTimeInterface $datein): self
    {
        $this->datein = $datein;

        return $this;
    }

    public function getDateen(): ?\DateTimeInterface
    {
        return $this->dateen;
    }

    public function setDateen(?\DateTimeInterface $dateen): self
    {
        $this->dateen = $dateen;

        return $this;
    }

    public function getDateph(): ?\DateTimeInterface
    {
        return $this->dateph;
    }

    public function setDateph(?\DateTimeInterface $dateph): self
    {
        $this->dateph = $dateph;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getUsername(): string
    {
        // TODO: Implement getUsername() method.
        return (string) $this->phone;
    }

    /**
     * @return int|null
     */
    public function getJcbankIdent(): ?int
    {
        return $this->jcbankIdent;
    }

    /**
     * @param int $jcbankIdent
     */
    public function setJcbankIdent(int $jcbankIdent): void
    {
        $this->jcbankIdent = $jcbankIdent;
    }


}
