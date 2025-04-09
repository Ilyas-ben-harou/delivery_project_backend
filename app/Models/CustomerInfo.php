<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInfo extends Model
{
    protected $fillable = [
        'full_name',
        'phone_number',
        'address',
        'city',
        'zone_geographic_id',
    ];

    public function zoneGeographic()
    {
        return $this->belongsTo(ZoneGeographic::class, 'zone_geographic_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
