<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Earning;
use App\Models\Livreur;
use App\Services\EarningsService;

class OrderObserver
{
    public function updated(Order $order)
    {
        // Vérifier si le statut a changé et est maintenant "completed"
        if ($order->isDirty('status') && $order->status === 'completed') {
            $this->createEarningRecord($order);
        }
    }

    protected function createEarningRecord(Order $order)
{
    if (!$order->livreur_id) {
        return;
    }

    $livreur = Livreur::find($order->livreur_id);
    $commission = EarningsService::calculateCommission($order, $livreur);

    Earning::create([
        'livreur_id' => $order->livreur_id,
        'order_id' => $order->id,
        'amount' => $order->amount,
        'commission_rate' => $commission['rate'],
        'commission_amount' => $commission['amount'],
        'status' => 'pending',
        'payment_date' => null
    ]);

    if ($livreur) {
        $livreur->increment('nomber_livraisons');
    }
}
}
