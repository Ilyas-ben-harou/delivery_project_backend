<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Livreur;
use App\Models\CustomerInfo;
use App\Models\ZoneGeographic;
use Illuminate\Support\Facades\DB;

class OrderAssignmentService
{
    /**
     * Assign an order to an available livreur based on matching zone_geographic
     *
     * @param Order $order
     * @return bool
     */
    public function assignLivreur(Order $order): bool
    {
        // Get the customer info for this order
        $customerInfo = CustomerInfo::findOrFail($order->customer_info_id);
        
        // Get the zone_geographic_id from customer info
        $zoneGeographicId = $customerInfo->zone_geographic_id;
        
        // Find available livreurs who work in this zone
        $availableLivreur = DB::table('livreurs')
            ->join('livreur_zone_geographic', 'livreurs.id', '=', 'livreur_zone_geographic.livreur_id')
            ->where('livreur_zone_geographic.zone_geographic_id', $zoneGeographicId)
            ->where('livreurs.disponible', true)
            ->orderBy('livreurs.nomber_livraisons', 'asc') // Assign to the livreur with fewer deliveries
            ->select('livreurs.id')
            ->first();
        
        // If an available livreur is found, assign the order
        if ($availableLivreur) {
            $order->livreur_id = $availableLivreur->id;
            $order->save();
            
            // Update the livreur's delivery count
            $livreur = Livreur::find($availableLivreur->id);
            $livreur->nomber_livraisons += 1;
            $livreur->save();
            
            return true;
        }
        
        // No available livreur found for this zone
        return false;
    }
}