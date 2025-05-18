<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CustomerInfo;
use App\Models\Livreur;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $clients = Client::pluck('id')->toArray();
        $customerInfos = CustomerInfo::pluck('id')->toArray();
        $livreurs = Livreur::pluck('id')->toArray();
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $products = [
            'Vase en céramique',
            'Meuble en bois',
            'Appareil électronique',
            'Vêtements',
            'Produits alimentaires'
        ];

        for ($i = 1; $i <= 100; $i++) {
            $collectionDate = Carbon::today()->addDays(rand(1, 10));
            $deliveryDate = (clone $collectionDate)->addDays(rand(1, 3));

            Order::create([
                'order_number' => 'CMD-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'description' => 'Description pour la commande ' . $i,
                'designation_product' => $products[array_rand($products)],
                'product_width' => rand(10, 100) . ' cm',
                'product_height' => rand(10, 100) . ' cm',
                'weight' => rand(1, 20) . ' kg',
                'collection_date' => $collectionDate,
                'status' => $statuses[array_rand($statuses)],
                'amount' => rand(100, 1000) + (rand(0, 99) / 100),
                'delivery_date' => rand(0, 1) ? $deliveryDate : null,
                'client_id' => $clients[array_rand($clients)],
                'livreur_id' => rand(0, 1) ? $livreurs[array_rand($livreurs)] : null,
                'customer_info_id' => $customerInfos[array_rand($customerInfos)],
            ]);
        }
    }
}
