<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    use HasFactory;

    protected $fillable = [
        'livreur_id',
        'order_id',
        'amount',
        'commission_rate',
        'commission_amount',
        'status',
        'payment_date'
    ];

    public function livreur()
    {
        return $this->belongsTo(Livreur::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
