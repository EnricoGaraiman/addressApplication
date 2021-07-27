<?php


namespace App\Service;


use App\Entity\Files;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getFiles(Products $products): array
    {
        return $this->entityManager->getRepository(Files::class)->findBy(['product'=>$products->getId(), 'type'=>0], ['position'=>'ASC']);
    }

    public function getThumbnail(Products $products): ?object
    {
        return $this->entityManager->getRepository(Files::class)->findOneBy(['product'=>$products->getId(), 'position'=>0, 'type'=>0]);
    }

    public function getDocument(Products $products): array
    {
        return $this->entityManager->getRepository(Files::class)->findBy(['product'=>$products->getId(), 'type'=>1]);
    }
}