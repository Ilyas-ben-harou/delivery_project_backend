<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneGeographic extends Model
{
    protected $table = 'zone_geographics';
    
    protected $fillable = [
        'city',
        'secteur',
    ];

    // Relation many-to-many avec les livreurs
    public function livreurs()
    {
        return $this->belongsToMany(Livreur::class, 'livreur_zone_geographic');
    }

    // Cette relation reste inchangée si elle est bien en one-to-many
    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class, 'zone_geographic_id');
    }
}
