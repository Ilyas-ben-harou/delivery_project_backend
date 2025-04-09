<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'amount',
        'payment_date',
        'status',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
