<?php


namespace App\Service;


use App\Entity\Addresses;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class DeleteAddressService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function deleteAddresses($idAddresses): array
    {
        try
        {
            foreach ($idAddresses as $idAddress)
            {
                $this->entityManager->remove($this->entityManager->getRepository(Addresses::class)->findOneBy(['id' => $idAddress]));
                $this->entityManager->flush();
            }
        }
        catch (Exception $e)
        {
            return ['message' => 'You already delete that address.', 'with' => 'danger'];
        }

        return ['message' => 'The addresses was been deleted with success', 'with' => 'success'];
    }
}