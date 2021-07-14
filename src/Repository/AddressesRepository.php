<?php

namespace App\Repository;

use App\Entity\Addresses;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Addresses|null find($id, $lockMode = null, $lockVersion = null)
 * @method Addresses|null findOneBy(array $criteria, array $orderBy = null)
 * @method Addresses[]    findAll()
 * @method Addresses[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Addresses::class);
    }

    public function getAddressesForOnePage($offset, $limit, $choose, $searchParameter, $orderBy, $orderType)
    {
        $query = $this->createQueryBuilder('a');
        $query
            ->innerJoin(City::class, 'c', 'with', 'a.city = c.id')
            ->innerJoin(Country::class, 'co', 'with', 'c.country = co.id')
            ->innerJoin(Users::class, 'u', 'with', 'u.id = a.user')
        ;

        if ($searchParameter !== '') {
            $query
                ->orWhere('a.address LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%')
                ->orWhere('c.name LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%')
                ->orWhere('co.name LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%')
                ->orWhere('u.name LIKE :searchParameter')
                ->setParameter('searchParameter', '%' . $searchParameter . '%')
            ;
        }

        if ($choose == 1) {
            $query->select('count(a.id)');
            return $query->getQuery()->getSingleScalarResult();
        }

        if($orderBy === 'city') {
            $query->orderBy('c.' . 'name', $orderType);
        }
        elseif($orderBy === 'country') {
            $query->orderBy('co.' . 'name', $orderType);
        }
        else {
            $query->orderBy('a.' . $orderBy, $orderType);
        }

        $query
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Addresses[] Returns an array of Addresses objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Addresses
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
