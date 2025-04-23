<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'cin',
        'adresse',
        'nomber_livraisons',
        'zone_geographic_id',
        'disponible',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zoneGoegraphic()
    {
        return $this->belongsTo(ZoneGeographic::class, 'zone_geographic_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
