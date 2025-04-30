<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'description',
        'designation_product',
        'product_width',
        'product_height',
        'weight',
        'collection_date',
        'status',
        'amount',
        'delivery_date',
        'client_id',
        'livreur_id',
        'customer_info_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'delivery_date' => 'date',
        'amount' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function livreur()
    {
        $livreurs=Livreur::with(['user']);
        return $this->belongsTo();
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class);
    }

    public function deliveryDocument()
    {
        return $this->hasOne(DeliveryDocument::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
