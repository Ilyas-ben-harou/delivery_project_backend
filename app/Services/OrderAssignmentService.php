<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Livreur;
use App\Models\CustomerInfo;
use App\Models\ZoneGeographic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderAssignmentService
{
    /**
     * Automatically assign an order to an available delivery person based on geographic zone
     *
     * @param Order $order The order to be assigned
     * @return bool Whether the assignment was successful
     */
    public function assignOrderToLivreur(Order $order)
    {
        // Skip if order already has a livreur assigned
        if ($order->livreur_id) {
            Log::info("Order #{$order->order_number} already assigned to livreur #{$order->livreur_id}");
            return false;
        }

        // Get customer information to determine geographic zone
        $customerInfo = $order->customerInfo;

        if (!$customerInfo || !$customerInfo->zone_geographic_id) {
            Log::warning("Order #{$order->order_number} cannot be assigned: Missing customer zone information");
            return false;
        }

        $zoneId = $customerInfo->zone_geographic_id;

        // Find available delivery personnel in the same zone
        $availableLivreurs = Livreur::where('zone_geographic_id', $zoneId)
            ->where('disponible', true)
            ->get();

        if ($availableLivreurs->isEmpty()) {
            Log::warning("No available delivery personnel in zone #{$zoneId} for order #{$order->order_number}");
            return false;
        }

        // Select livreur with the fewest current deliveries
        $selectedLivreur = $this->selectOptimalLivreur($availableLivreurs);

        // Assign order to selected livreur
        $order->livreur_id = $selectedLivreur->id;
        $order->status = 'assigned';
        $order->save();

        // Update delivery person's delivery count
        $selectedLivreur->nomber_livraisons += 1;
        $selectedLivreur->save();

        Log::info("Order #{$order->order_number} assigned to livreur #{$selectedLivreur->id} in zone #{$zoneId}");

        return true;
    }

    /**
     * Select the optimal delivery person based on current workload
     *
     * @param \Illuminate\Database\Eloquent\Collection $livreurs
     * @return Livreur
     */
    private function selectOptimalLivreur($livreurs)
    {
        // Start with the first livreur
        $optimalLivreur = $livreurs->first();
        $minDeliveries = $optimalLivreur->nomber_livraisons;

        // Find the livreur with the fewest current deliveries
        foreach ($livreurs as $livreur) {
            if ($livreur->nomber_livraisons < $minDeliveries) {
                $optimalLivreur = $livreur;
                $minDeliveries = $livreur->nomber_livraisons;
            }
        }

        return $optimalLivreur;
    }

    /**
     * Batch process to assign all unassigned orders
     *
     * @return array Statistics about the assignment process
     */
    public function batchAssignOrders()
    {
        $stats = [
            'total' => 0,
            'assigned' => 0,
            'failed' => 0
        ];

        // Get all unassigned orders
        $unassignedOrders = Order::whereNull('livreur_id')
            ->whereNotNull('customer_info_id')
            ->orderBy('collection_date')
            ->get();

        $stats['total'] = $unassignedOrders->count();

        foreach ($unassignedOrders as $order) {
            $result = $this->assignOrderToLivreur($order);

            if ($result) {
                $stats['assigned']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Check and update livreur availability based on delivery count
     * 
     * @param int $maxDeliveries Maximum number of deliveries a livreur can handle
     * @return int Number of livreurs whose status was updated
     */
    public function updateLivreurAvailability($maxDeliveries = 10)
    {
        $updated = 0;

        // Find livreurs who have reached max capacity
        $overloadedLivreurs = Livreur::where('disponible', true)
            ->where('nomber_livraisons', '>=', $maxDeliveries)
            ->get();

        foreach ($overloadedLivreurs as $livreur) {
            $livreur->disponible = false;
            $livreur->save();
            $updated++;
        }

        // Find livreurs who now have capacity
        $availableLivreurs = Livreur::where('disponible', false)
            ->where('nomber_livraisons', '<', $maxDeliveries)
            ->get();

        foreach ($availableLivreurs as $livreur) {
            $livreur->disponible = true;
            $livreur->save();
            $updated++;
        }

        return $updated;
    }

    /**
     * Reassign orders if a livreur becomes unavailable
     *
     * @param Livreur $livreur The delivery person who is no longer available
     * @return array Statistics about reassigned orders
     */
    public function reassignOrdersFromUnavailableLivreur(Livreur $livreur)
    {
        $stats = [
            'total' => 0,
            'reassigned' => 0,
            'failed' => 0
        ];

        if ($livreur->disponible) {
            return $stats;
        }

        $pendingOrders = Order::where('livreur_id', $livreur->id)
            ->whereIn('status', ['assigned', 'pending'])
            ->get();

        $stats['total'] = $pendingOrders->count();

        foreach ($pendingOrders as $order) {
            $order->livreur_id = null;
            $order->save();

            // Tentative de réassignation
            $result = $this->assignOrderToLivreur($order);

            if ($result) {
                $stats['reassigned']++;
                Log::info("Order #{$order->order_number} reassigned successfully");
            } else {
                $stats['failed']++;
                Log::warning("Failed to reassign Order #{$order->order_number}. No available livreur.");
            }
        }

        $livreur->nomber_livraisons = $livreur->orders()
            ->whereIn('status', ['assigned', 'pending', 'in_progress'])
            ->count();
        $livreur->save();

        return $stats;
    }
}
