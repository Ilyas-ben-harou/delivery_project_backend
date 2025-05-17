<?php

namespace Database\Seeders;

use App\Models\Earning;
use App\Models\Order;
use Illuminate\Database\Seeder;

class EarningSeeder extends Seeder
{
    public function run()
    {
        $orders = Order::whereNotNull('livreur_id')
                     ->whereIn('status', ['delivered', 'shipped'])
                     ->get();

        foreach ($orders as $order) {
            $commissionRate = rand(10, 20); // 10% to 20% commission
            $commissionAmount = round($order->amount * ($commissionRate / 100), 2);

            Earning::create([
                'livreur_id' => $order->livreur_id,
                'order_id' => $order->id,
                'amount' => $order->amount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => $order->status == 'delivered' ? 'paid' : 'pending',
                'payment_date' => $order->status == 'delivered' ? $order->delivery_date : null,
            ]);
        }
    }
}
