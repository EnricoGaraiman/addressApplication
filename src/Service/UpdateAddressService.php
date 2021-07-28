<?php


namespace App\Service;


use App\Entity\Addresses;
use Doctrine\ORM\EntityManagerInterface;

class UpdateAddressService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function updateAddress($input, $slug, $user): array
    {
        $address = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id' => $slug]);
        $address->setCountry($input['country'])
            ->setCity($input['city'])
            ->setAddress($input['address']);
        if (isset($input['default']) )
        {
            $addressDefault = $this->entityManager->getRepository(Addresses::class)->findOneBy(['user' => $user, 'isDefault' => 1]);
            if($addressDefault !== null)
            {
                foreach ($user->getAddress() as $adr)
                {
                    $adr->setIsDefault(0);
                    $this->entityManager->persist($adr);
                    $this->entityManager->flush();
                }
            }
            $address->setIsDefault(1);
        }
        else
        {
            $address->setIsDefault(0);
        }
        $this->entityManager->persist($address);
        $this->entityManager->flush();
        return ['message' => 'Update with success', 'with' => 'success'];
    }
}