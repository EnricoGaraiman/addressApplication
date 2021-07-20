<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UsersRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Addresses::class, mappedBy="user")
     */
    private $address;

    /**
     * @ORM\OneToMany(targetEntity=Orders::class, mappedBy="user")
     */
    private $userorder;

    public function __construct()
    {
        $this->address = new ArrayCollection();
        $this->userorder = new ArrayCollection();
    }

    public function getDefaultAddress()
    {
        return $this->address->filter(function($element) {
            return $element->getIsDefault() == 1;
        });
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
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
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
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
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Addresses[]
     */
    public function getAddress(): Collection
    {
        return $this->address;
    }

    public function addAddress(Addresses $address): self
    {
        if (!$this->address->contains($address)) {
            $this->address[] = $address;
            $address->setAddresses($this);
        }

        return $this;
    }

    public function removeAddress(Addresses $address): self
    {
        if ($this->address->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getAddresses() === $this) {
                $address->setAddresses(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Orders[]
     */
    public function getUserorder(): Collection
    {
        return $this->userorder;
    }

    public function addUserorder(Orders $userorder): self
    {
        if (!$this->userorder->contains($userorder)) {
            $this->userorder[] = $userorder;
            $userorder->setUser($this);
        }

        return $this;
    }

    public function removeUserorder(Orders $userorder): self
    {
        if ($this->userorder->removeElement($userorder)) {
            // set the owning side to null (unless already changed)
            if ($userorder->getUser() === $this) {
                $userorder->setUser(null);
            }
        }

        return $this;
    }

}
