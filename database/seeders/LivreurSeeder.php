<?php

namespace Database\Seeders;

use App\Models\Livreur;
use App\Models\User;
use Illuminate\Database\Seeder;

class LivreurSeeder extends Seeder
{
    public function run()
    {
        $livreurs = User::where('role', 'livreur')->get();
        $streets = ['Mohammed V', 'Hassan II', 'Al Massira', 'Al Qods', 'Moulay Ismail'];

        foreach ($livreurs as $index => $user) {
            Livreur::create([
                'user_id' => $user->id,
                'first_name' => 'Livreur',
                'last_name' => 'Nom' . ($index + 1),
                'cin' => 'CD' . (200000 + $index),
                'adresse' => ($index + 10) . ' Avenue ' . $streets[$index % 5],
                'disponible' => $index % 3 != 0, // Make some unavailable
                'nomber_livraisons' => $index * 5,
                'unavailable_start' => $index % 3 == 0 ? now()->subDays(2) : null,
                'unavailable_end' => $index % 3 == 0 ? now()->addDays(1) : null,
                'unavailable_reason' => $index % 3 == 0 ? 'Congé' : null,
            ]);
        }
    }
}
