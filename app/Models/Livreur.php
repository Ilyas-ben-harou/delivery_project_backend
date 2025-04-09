<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'zone_geographic_id',
        'is_available',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zoneGoegraphique()
    {
        return $this->belongsTo(ZoneGeographic::class, 'zone_geographic_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
