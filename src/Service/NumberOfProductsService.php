<?php


namespace App\Service;

use Symfony\Component\HttpFoundation\Request;


class NumberOfProductsService
{
    public function getNumberOfProducts(Request $request):int
    {
        $numberOfProducts = 0;
        if($request->cookies->get('cart') !== null)
        {
            foreach (unserialize($request->cookies->get('cart')) as $product => $quantity)
            {
                $numberOfProducts += $quantity;
            }
        }
        return $numberOfProducts;
    }
}