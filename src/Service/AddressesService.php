<?php


namespace App\Service;


use App\Entity\City;
use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AddressesService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function import($file, $choose, $directory): array
    {
        $message = ['message' => '', 'with' => ''];

        $filePathName = md5(uniqid()) . $file->getClientOriginalName();
        try
        {
            $file->move($directory, $filePathName);
        }
        catch (FileException $e)
        {
            $message = ['message' => 'Error. Try again', 'with' => 'danger'];
        }
        $spreadsheet = IOFactory::load($directory.'/'.$filePathName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if($choose === 'Countries')
        {
            foreach ($sheetData as $sheetRow)
            {
                if($this->entityManager->getRepository(Country::class)->findOneBy(['code'=>$sheetRow['B']]) === null)
                {
                    $country = new Country();
                    $country->setName($sheetRow['A'])
                        ->setCode($sheetRow['B']);
                    $this->entityManager->persist($country);
                    $this->entityManager->flush();
                }
            }
            $message = ['message' => 'Import with success', 'with' => 'success'];
        }

        if($choose === 'Cities')
        {
            foreach ($sheetData as $sheetRow)
            {
                if($this->entityManager->getRepository(City::class)->findOneBy(['name'=>$sheetRow['B']]) === null)
                {
                    $countryId = $this->entityManager->getRepository(Country::class)->findOneBy(['name'=>$sheetRow['A']]);
                    $city = new City();
                    $city->setName($sheetRow['B'])
                        ->setCountry($countryId);
                    $this->entityManager->persist($city);
                    $this->entityManager->flush();
                }
            }
            $message = ['message' => 'Import with success', 'with' => 'success'];
        }

        return $message;
    }

}