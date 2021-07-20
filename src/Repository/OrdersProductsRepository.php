<?php

namespace App\Repository;

use App\Entity\OrdersProducts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrdersProducts|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrdersProducts|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrdersProducts[]    findAll()
 * @method OrdersProducts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdersProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrdersProducts::class);
    }

    // /**
    //  * @return OrdersProducts[] Returns an array of OrdersProducts objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OrdersProducts
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
