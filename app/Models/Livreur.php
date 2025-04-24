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
        'disponible',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Nouvelle relation many-to-many avec ZoneGeographic
    public function zones()
    {
        return $this->belongsToMany(ZoneGeographic::class, 'livreur_zone_geographic');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
