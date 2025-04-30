<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use App\Models\Order;
use App\Models\CustomerInfo;
use App\Services\OrderAssignmentService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $orderAssignmentService;

    public function __construct(OrderAssignmentService $orderAssignmentService)
    {
        $this->orderAssignmentService = $orderAssignmentService;
    }
    public function index(Request $request)
{
    $query = Order::query();

    if ($request->has('status') && $request->status !== '') {
        $query->where('status', $request->status);
    }

    if ($request->has('search') && $request->search !== '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('order_number', 'like', "%$search%")
              ->orWhere('designation_product', 'like', "%$search%");
        });
    }

    $perPage = $request->input('per_page', 5);
    return $query->paginate($perPage);
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
    public function show($id)
    {
        $order = Order::with(['customerInfo', 'livreur.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
