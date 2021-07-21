<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    public function getProductsForOnePage($offset, $limit, $choose, $searchParameter, $orderBy, $orderType)
    {
        $query = $this->createQueryBuilder('p');
        if ($searchParameter !== '') {
            $query->orWhere('p.name LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%')
                ->orWhere('p.price LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%');
        }

        if ($choose == 1) {
            $query->select('count(p.id)');
            return $query->getQuery()->getSingleScalarResult();
        }

        $query->orderBy('p.' . $orderBy, $orderType)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Products[] Returns an array of Products objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Products
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
