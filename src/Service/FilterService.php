<?php


namespace App\Service;


class FilterService
{
    public function orderDropdownProcess($order)
    {
        switch ($order) {
            case 'name_desc':
                $orderBy = 'name';
                $orderType = 'desc';
                break;
            case 'email_asc':
                $orderBy = 'email';
                $orderType = 'asc';
                break;
            case 'email_desc':
                $orderBy = 'email';
                $orderType = 'desc';
                break;
            default:
                $orderBy = 'name';
                $orderType = 'asc';
        }
        return [
            'orderBy' => $orderBy,
            'orderType' => $orderType
        ];
    }

    public function orderDropdownProcessInAddresses($order)
    {
        switch ($order) {
//            case 'country_desc':
//                $orderBy = 'country';
//                $orderType = 'desc';
//                break;
            case 'city_asc':
                $orderBy = 'city';
                $orderType = 'asc';
                break;
            case 'city_desc':
                $orderBy = 'city';
                $orderType = 'desc';
                break;
            case 'address_asc':
                $orderBy = 'address';
                $orderType = 'asc';
                break;
            case 'address_desc':
                $orderBy = 'address';
                $orderType = 'desc';
                break;
            default:
                $orderBy = 'city';
                $orderType = 'asc';
//                $orderBy = 'country';
//                $orderType = 'asc';
        }
        return [
            'orderBy' => $orderBy,
            'orderType' => $orderType
        ];
    }
}
