<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CustomerInfo;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();

        return response()->json($orders);
    }
    public function store(StoreOrderRequest $request)
    {
        // Create or update customer info
        $customerInfo = CustomerInfo::create(
            [
                'phone_number' => $request->customer_phone_number,
                'full_name' => $request->customer_full_name,
                'address' => $request->customer_address,
                'city' => $request->customer_city,
                'zone_geographic_id' => $request->zone_geographic_id,
            ]
        );

        // Generate unique order number
        $orderNumber = 'ORD-' . Str::upper(Str::random(8));

        // Create the order
        $order = Order::create([
            'order_number' => $orderNumber,
            'description' => $request->description,
            'designation_product' => $request->designation_product,
            'product_width' => $request->product_width,
            'product_height' => $request->product_height,
            'weight' => $request->weight,
            'collection_date' => $request->collection_date,
            'amount' => $request->amount,
            'client_id' => $request->client_id, // Current authenticated client
            'customer_info_id' => $customerInfo->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }
}
