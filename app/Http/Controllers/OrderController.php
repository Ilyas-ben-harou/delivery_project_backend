<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Livreur;
use App\Models\Order;
use App\Models\CustomerInfo;
use App\Services\OrderAssignmentService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $orderAssignmentService;
    protected $pricingService;

    public function __construct(
        OrderAssignmentService $orderAssignmentService,
        PricingService $pricingService
    ) {
        $this->orderAssignmentService = $orderAssignmentService;
        $this->pricingService = $pricingService;
    }
    
    public function index()
    {
        $orders = Order::all();

        return response()->json($orders);
    }
    
    public function getLivreurOrders(Request $request)
    {
        $orders = Order::where('livreur_id', $request->livreur_id)->with('customerInfo')->get();

        return response()->json($orders);
    }
    
    public function store(StoreOrderRequest $request)
    {
        // Create or update customer info
        $customerInfo = CustomerInfo::create([
            'phone_number' => $request->customer_phone_number,
            'full_name' => $request->customer_full_name,
            'address' => $request->customer_address,
            'city' => $request->customer_city,
            'zone_geographic_id' => $request->zone_geographic_id,
        ]);

        // Generate unique order number
        $orderNumber = 'ORD-' . Str::upper(Str::random(8));

        // Get price based on the city using our pricing service
        $amount = $request->has('amount') && $request->amount > 0 
            ? $request->amount 
            : $this->pricingService->getPriceForZone($request->zone_geographic_id);

        // Create the order
        $order = Order::create([
            'order_number' => $orderNumber,
            'description' => $request->description,
            'designation_product' => $request->designation_product,
            'product_width' => $request->product_width,
            'product_height' => $request->product_height,
            'weight' => $request->weight,
            'collection_date' => $request->collection_date,
            'amount' => $amount,
            'client_id' => $request->client_id, // Current authenticated client
            'customer_info_id' => $customerInfo->id,
        ]);

        // Find available livreur in the same zone geographic
        $availableLivreur = DB::table('livreurs')
            ->join('livreur_zone_geographic', 'livreurs.id', '=', 'livreur_zone_geographic.livreur_id')
            ->where('livreur_zone_geographic.zone_geographic_id', $request->zone_geographic_id)
            ->where('livreurs.disponible', true)
            ->orderBy('livreurs.nomber_livraisons', 'asc') // Assign to the livreur with fewer deliveries
            ->select('livreurs.id')
            ->first();

        $assigned = false;
        if ($availableLivreur) {
            // Update the order with the assigned livreur
            $order->livreur_id = $availableLivreur->id;
            $order->save();

            // Update the livreur's delivery count
            $livreur = Livreur::find($availableLivreur->id);
            $livreur->nomber_livraisons += 1;
            $livreur->save();

            $assigned = true;
        }

        return response()->json([
            'success' => true,
            'message' => $assigned ? 'Order created and assigned to a deliverer' : 'Order created successfully, but no available deliverer found in this zone',
            'data' => $order,
            'assigned' => $assigned
        ], 201);
    }
    
    // Remaining methods are unchanged...
    // (show, update, updateStatus, assignToLivreur, destroy)
    
    public function show($id)
    {
        $order = Order::with(['customerInfo', 'livreur.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
    
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            // Find the existing order
            $order = Order::findOrFail($id);

            // Begin database transaction
            DB::beginTransaction();

            // Update order details
            $orderData = $request->only([
                'order_number',
                'designation_product',
                'description',
                'product_width',
                'product_height',
                'weight',
                'collection_date',
                'delivery_date',
                'amount',
                'status'
            ]);

            // Update the order
            $order->update($orderData);

            // Update or create customer information
            if ($request->has('customer_info')) {
                $customerInfoData = $request->input('customer_info');
                
                // Find the customer info
                $customerInfo = CustomerInfo::find($order->customer_info_id);
                
                if ($customerInfo) {
                    // Update existing customer info
                    $customerInfo->update([
                        'full_name' => $customerInfoData['full_name'],
                        'phone_number' => $customerInfoData['phone_number'],
                        'address' => $customerInfoData['address'],
                        'city' => $customerInfoData['city']
                    ]);
                } else {
                    // Create new customer info
                    $customerInfo = CustomerInfo::create([
                        'full_name' => $customerInfoData['full_name'],
                        'phone_number' => $customerInfoData['phone_number'],
                        'address' => $customerInfoData['address'],
                        'city' => $customerInfoData['city'],
                        'zone_geographic_id' => $customerInfoData['zone_geographic_id'] ?? 1 // Default if not provided
                    ]);
                    
                    // Associate with order
                    $order->customer_info_id = $customerInfo->id;
                    $order->save();
                }
            }

            // Commit the transaction
            DB::commit();

            // Return the updated order with relationships
            return response()->json([
                'message' => 'Order updated successfully',
                'data' => $order->load([
                    'customerInfo',
                    'livreur'
                ])
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Order Update Error: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = $request->status;
        if ($request->status == 'delivered') {
            $order->delivery_date = now();
        }
        if ($request->status == 'failed') {
            $order->failure_reason = $request->failure_reason;
        }
        if ($request->status == 'pending') {
            $order->failure_reason = null;
            $order->delivery_date = null;
        }
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }
    
    public function assignToLivreur(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $livreur = Livreur::findOrFail($request->livreur_id);

        // Check if the livreur is available
        if (!$livreur->disponible) {
            return response()->json([
                'success' => false,
                'message' => 'Livreur is not available'
            ], 400);
        }

        // Assign the order to the livreur
        $order->livreur_id = $livreur->id;
        $order->save();

        // Update the livreur's delivery count
        $livreur->nomber_livraisons += 1;
        $livreur->save();

        return response()->json([
            'success' => true,
            'message' => 'Order assigned to livreur successfully',
            'data' => $order
        ]);
    }
    
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }
}