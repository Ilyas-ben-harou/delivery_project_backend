<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        // autres champs nécessaires
    ];

    // Relations
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
