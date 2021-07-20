<?php

namespace App\Entity;

use App\Repository\AddressesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AddressesRepository::class)
 */
class Addresses
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="address")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDefault;

    /**
     * @ORM\ManyToOne(targetEntity=City::class, inversedBy="addresses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $city;

    /**
     * @ORM\OneToMany(targetEntity=Orders::class, mappedBy="address")
     */
    private $orderAddress;

    public function __construct()
    {
        $this->orderAddress = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Collection|Orders[]
     */
    public function getOrderAddress(): Collection
    {
        return $this->orderAddress;
    }

    public function addOrderAddress(Orders $orderAddress): self
    {
        if (!$this->orderAddress->contains($orderAddress)) {
            $this->orderAddress[] = $orderAddress;
            $orderAddress->setAddress($this);
        }

        return $this;
    }

    public function removeOrderAddress(Orders $orderAddress): self
    {
        if ($this->orderAddress->removeElement($orderAddress)) {
            // set the owning side to null (unless already changed)
            if ($orderAddress->getAddress() === $this) {
                $orderAddress->setAddress(null);
            }
        }

        return $this;
    }
}
