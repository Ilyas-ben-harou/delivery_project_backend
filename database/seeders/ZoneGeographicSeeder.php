<?php

namespace Database\Seeders;

use App\Models\ZoneGeographic;
use Illuminate\Database\Seeder;

class ZoneGeographicSeeder extends Seeder
{
    public function run()
    {
        $sectors = [
            'Gueliz',
            'Hivernage',
            'Medina',
            'Palmeraie',
            'Sidi Youssef Ben Ali',
            'Daoudiate',
            'Azzouzia',
            'Agdal',
            'Ménara',
            'Assif'
        ];

        foreach ($sectors as $sector) {
            ZoneGeographic::create([
                'city' => 'Marrakech',
                'secteur' => $sector,
                'price' => collect([30, 35, 40, 45])->random()
            ]);
        }
    }
}
