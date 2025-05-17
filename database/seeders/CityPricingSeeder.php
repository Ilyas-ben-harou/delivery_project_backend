<?php

namespace Database\Seeders;

use App\Models\CityPricing;
use Illuminate\Database\Seeder;

class CityPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define fixed prices for different cities
        $cityPrices = [
            'Casablanca' => 35.0,
            'Rabat' => 40.0,
            'Marrakech' => 45.0,
            'Fes' => 40.0,
            'Tanger' => 45.0,
            'Agadir' => 45.0,
            'Tetouan' => 40.0,
            'Meknes' => 40.0,
            'Oujda' => 45.0,
            'Kenitra' => 35.0,
        ];

        // Insert the city pricing data
        foreach ($cityPrices as $city => $price) {
            CityPricing::updateOrCreate(
                ['city' => $city],
                ['price' => $price]
            );
        }
    }
}