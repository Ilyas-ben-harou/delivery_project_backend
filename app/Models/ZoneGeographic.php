<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneGeographic extends Model
{
    protected $table = 'zone_geographics';
    
    protected $fillable = [
        'city',
        'region',
    ];

    public function livreurs()
    {
        return $this->hasMany(Livreur::class, 'zone_geographic_id');
    }

    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class, 'zone_geographic_id');
    }
}
