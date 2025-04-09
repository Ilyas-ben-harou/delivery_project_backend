<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDocument extends Model
{
    protected $fillable = [
        'order_number',
        'designation_product',
        'customer_phone',
        'customer_address',
        'amount',
        'qr_code',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
