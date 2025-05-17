<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run()
    {
        $clients = User::where('role', 'client')->get();
        $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Fès'];

        foreach ($clients as $index => $user) {
            Client::create([
                'user_id' => $user->id,
                'nom' => 'Client',
                'prenom' => 'Prénom' . ($index + 1),
                'boutique' => 'Boutique ' . ($index + 1),
                'cin' => 'AB' . (100000 + $index),
                'banque' => ['CIH', 'BMCE', 'SGMB', 'BP', 'Attijari'][$index % 5],
                'rib' => 'FR76' . (3000 + $index) . '100794' . (123456 + $index),
                'ville' => $cities[$index % 5],
                'adresse' => ($index + 1) . ' Rue des Commerçants',
            ]);
        }
    }
}
