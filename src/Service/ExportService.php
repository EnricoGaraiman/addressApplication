<?php


namespace App\Service;

use App\Entity\Files;
use Doctrine\ORM\EntityManagerInterface;

class ExportService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exportSelection($selection, $product, $request)
    {
        switch ($selection)
        {
            case 'Id':
                $return = $product->getId();
                break;
            case 'Name':
                $return = $product->getName();
                break;
            case 'Description':
                $return = $product->getDescription();
                break;
            case 'Price':
                $return = $product->getPrice();
                break;
            case 'Images':
                $return = $this->getFiles($this->entityManager->getRepository(Files::class)->findBy(['product'=>$product->getId(), 'type'=>0]), $request);
                break;
            case 'Documents':
                $return = $this->getFiles($this->entityManager->getRepository(Files::class)->findBy(['product'=>$product->getId(), 'type'=>1]), $request);
                break;
            default:
                $return = $product->getId();
        }
        return $return;
    }

    public function getFiles($files, $request): string
    {
        $return = "";
        foreach ($files as $file)
        {
            $return = $return.sprintf("%s://%s", $request->getScheme(), $request->getHttpHost())."/uploads/images/".$file->getName().","." \n ";
        }
        return $return;
    }
}