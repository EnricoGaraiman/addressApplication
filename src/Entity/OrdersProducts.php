<?php

namespace App\Entity;

use App\Repository\OrdersProductsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrdersProductsRepository::class)
 */
class OrdersProducts
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Orders::class, inversedBy="ordersProducts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parentOrder;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="ordersProducts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentOrder(): ?Orders
    {
        return $this->parentOrder;
    }

    public function setParentOrder(?Orders $parentOrder): self
    {
        $this->parentOrder = $parentOrder;

        return $this;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;

        return $this;
    }
}
