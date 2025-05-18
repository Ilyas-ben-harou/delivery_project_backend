<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneGeographic extends Model
{
    protected $table = 'zone_geographics';
    
    protected $fillable = [
        'city',
        'secteur',
        'price', // Added price to fillable attributes
    ];

    // Relation many-to-many with livreurs (delivery personnel)
    public function livreurs()
    {
        return $this->belongsToMany(Livreur::class, 'livreur_zone_geographic');
    }

    // Relation one-to-many with customer information
    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class, 'zone_geographic_id');
    }
    
    // Orders delivered to this zone (through customer info)
    public function orders()
    {
        return $this->hasManyThrough(Order::class, CustomerInfo::class);
    }
}