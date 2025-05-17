<?php

namespace Database\Seeders;

use App\Models\DeliveryDocument;
use App\Models\Order;
use Illuminate\Database\Seeder;

class DeliveryDocumentSeeder extends Seeder
{
    public function run()
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            DeliveryDocument::create([
                'order_number' => $order->order_number,
                'designation_product' => $order->designation_product,
                'customer_phone' => $order->customerInfo->phone_number,
                'customer_address' => $order->customerInfo->address,
                'amount' => $order->amount,
                'qr_code' => 'QR-' . $order->order_number . '-' . bin2hex(random_bytes(4)),
                'order_id' => $order->id,
            ]);
        }
    }
}
