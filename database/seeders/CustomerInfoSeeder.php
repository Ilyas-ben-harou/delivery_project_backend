<?php

namespace Database\Seeders;

use App\Models\CustomerInfo;
use App\Models\ZoneGeographic;
use Illuminate\Database\Seeder;

class CustomerInfoSeeder extends Seeder
{
    public function run()
    {
        $zones = ZoneGeographic::pluck('id')->toArray();
        $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Fès'];

        for ($i = 1; $i <= 100; $i++) {
            CustomerInfo::create([
                'full_name' => 'Client ' . $i,
                'phone_number' => '06' . (40000000 + $i),
                'address' => $i . ' Rue des Fleurs, ' . $cities[$i % 5],
                'city' => $cities[$i % 5],
                'zone_geographic_id' => $zones[array_rand($zones)],
            ]);
        }
    }
}
