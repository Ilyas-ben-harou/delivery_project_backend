<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Livreur;

class EarningsService
{
    public static function calculateCommission(Order $order, Livreur $livreur)
    {
        // Logique de calcul de commission plus sophistiquée
        $baseRate = 15; // 15% de base

        // Facteurs supplémentaires qui pourraient affecter la commission
        $distanceFactor = 0; // Pourrait être calculé en fonction de la distance
        $weightFactor = $order->weight > 10 ? 2 : 0; // 2% supplémentaire pour les colis lourds

        $totalRate = $baseRate + $distanceFactor + $weightFactor;
        $commissionAmount = ($order->amount * $totalRate) / 100;

        return [
            'rate' => $totalRate,
            'amount' => $commissionAmount
        ];
    }
}
