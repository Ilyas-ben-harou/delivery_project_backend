<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class UpdateOrderRequest extends FormRequest
{
    public function authorize()
    {
        // Add any authorization logic
        return true;
    }

    public function rules()
    {
        return [
            'designation_product' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'product_width' => 'nullable|string|max:50',
            'product_height' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric',
            'collection_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:collection_date',
            'amount' => 'required|numeric|min:0',
            'status' => 'in:pending,processing,delivered,cancelled',
            
            'customer_info.full_name' => 'required|string|max:255',
            'customer_info.phone_number' => 'nullable|string|max:20',
            'customer_info.address' => 'nullable|string|max:500',
            'customer_info.city' => 'nullable|string|max:100'
        ];
    }

    public function messages()
    {
        return [
            'delivery_date.after_or_equal' => 'Delivery date must be on or after the collection date.',
            'amount.min' => 'Amount must be a positive number.'
        ];
    }
}