<?php

namespace App\Helpers;

class Currency {
    public static function list()
    {
        return [
            [
                'id' => 1,
                'countryName' => 'INDIA',
                'currencyCode'  => "INR"
            ],
            [
                'id' => 2,
                'countryName' => 'UNITED STATE',
                'currencyCode'  => "USD"
            ],
            [
                'id' => 3,
                'countryName' => 'UNITED KINGDOM',
                'currencyCode'  => "GBP"
            ],
            [
                'id' => 4,
                'countryName' => 'GERMANY',
                'currencyCode'  => "EUR"
            ],
        ];
    }
}
?>