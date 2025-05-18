<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            ZoneGeographicSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            LivreurSeeder::class,
            CustomerInfoSeeder::class,
            OrderSeeder::class,
            DeliveryDocumentSeeder::class,
            EarningSeeder::class,
        ]);
    }
}
