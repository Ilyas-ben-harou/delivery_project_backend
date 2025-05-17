<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create Admin User
        $admin = User::create([
            'email' => 'admin@deliveryapp.com',
            'password' => Hash::make('admin123'),
            'phone_number' => '0612345678',
            'role' => 'admin',
        ]);

        // Create Client Users
        $client1 = User::create([
            'email' => 'client1@example.com',
            'password' => Hash::make('client123'),
            'phone_number' => '0623456789',
            'role' => 'client',
        ]);

        $client2 = User::create([
            'email' => 'client2@example.com',
            'password' => Hash::make('client123'),
            'phone_number' => '0634567890',
            'role' => 'client',
        ]);

        // Create Livreur Users
        $livreur1 = User::create([
            'email' => 'livreur1@example.com',
            'password' => Hash::make('livreur123'),
            'phone_number' => '0645678901',
            'role' => 'livreur',
        ]);

        $livreur2 = User::create([
            'email' => 'livreur2@example.com',
            'password' => Hash::make('livreur123'),
            'phone_number' => '0656789012',
            'role' => 'livreur',
        ]);

        // You can add more users as needed
    }
}
