<?php

namespace App\Services;

use App\Models\CityPricing;
use App\Models\ZoneGeographic;

class PricingService
{
    /**
     * Get the price for a specific city
     * 
     * @param string $city The city name
     * @return float The price for the city, or default price if not found
     */
    public function getPriceForCity($city)
    {
        // Default price if city pricing is not found
        $defaultPrice = 40.0;
        
        // Try to find city pricing in the database
        $pricing = CityPricing::where('city', $city)->first();
        
        // Return the price if found, otherwise return default price
        return $pricing ? $pricing->price : $defaultPrice;
    }
    
    /**
     * Get the price for a specific zone geographic ID
     * 
     * @param int $zoneId The zone geographic ID
     * @return float The price for the zone, or default price if not found
     */
    public function getPriceForZone($zoneId)
    {
        // Default price if zone or pricing is not found
        $defaultPrice = 40.0;
        
        // Try to find the zone
        $zone = ZoneGeographic::find($zoneId);
        if (!$zone) {
            return $defaultPrice;
        }
        
        // Try to find city pricing in the database
        return $this->getPriceForCity($zone->city);
    }
}