<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_full_name' => 'required|string|max:255',
            'customer_phone_number' => 'required|string|max:20',
            'customer_address' => 'required|string|max:255',
            'customer_city' => 'required|string|max:255',
            'zone_geographic_id' => 'required|exists:zone_geographics,id',
            'designation_product' => 'required|string|max:255',
            'product_width' => 'nullable|string|max:50',
            'product_height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'collection_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'client_id' => 'required|integer',
        ];
    }
}