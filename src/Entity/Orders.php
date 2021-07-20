<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrdersRepository::class)
 */
class Orders
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="userorder")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Addresses::class, inversedBy="orderAddress")
     * @ORM\JoinColumn(nullable=false)
     */
    private $address;

    /**
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * @ORM\Column(type="datetime")
     */
    private $orderDate;

    /**
     * @ORM\OneToMany(targetEntity=OrdersProducts::class, mappedBy="parentOrder", orphanRemoval=true)
     */
    private $ordersProducts;

    public function __construct()
    {
        $this->ordersProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAddress(): ?Addresses
    {
        return $this->address;
    }

    public function setAddress(?Addresses $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * @return Collection|OrdersProducts[]
     */
    public function getOrdersProducts(): Collection
    {
        return $this->ordersProducts;
    }

    public function addOrdersProduct(OrdersProducts $ordersProduct): self
    {
        if (!$this->ordersProducts->contains($ordersProduct)) {
            $this->ordersProducts[] = $ordersProduct;
            $ordersProduct->setParentOrder($this);
        }

        return $this;
    }

    public function removeOrdersProduct(OrdersProducts $ordersProduct): self
    {
        if ($this->ordersProducts->removeElement($ordersProduct)) {
            // set the owning side to null (unless already changed)
            if ($ordersProduct->getParentOrder() === $this) {
                $ordersProduct->setParentOrder(null);
            }
        }

        return $this;
    }
}
