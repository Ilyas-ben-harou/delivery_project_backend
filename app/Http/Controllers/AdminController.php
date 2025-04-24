<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getOrders(Request $request)
    {
        try {
            // Start with a base query
            $query = Order::with(['client', 'livreur', 'customerInfo'])
                ->orderBy('created_at', 'desc');

            // Apply filters if they exist
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'tous') {
                $query->where('status', $request->status);
            }

            // Filter by date
            if ($request->has('date')) {
                $date = Carbon::parse($request->date)->format('Y-m-d');
                $query->whereDate('collection_date', $date);
            }

            // Filter by livreur
            if ($request->has('livreur')) {
                if ($request->livreur === 'non_assigné') {
                    $query->whereNull('livreur_id');
                } elseif ($request->livreur !== 'tous') {
                    $query->where('livreur_id', $request->livreur);
                }
            }

            // Paginate the results
            $perPage = $request->per_page ?? 10;
            $orders = $query->paginate($perPage);

            // Transform the data to match the front-end expectations
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'description' => $order->description,
                    'designation_product' => $order->designation_product,
                    'product_width' => $order->product_width,
                    'product_height' => $order->product_height,
                    'weight' => $order->weight,
                    'collection_date' => $order->collection_date,
                    'status' => $order->status,
                    'amount' => $order->amount,
                    'delivery_date' => $order->delivery_date,
                    'client' => [
                        'id' => $order->client->id,
                        'name' => $order->client->nom. ' ' . $order->client->prenom,
                        'phone' => User::where('id', $order->client->id)->value('phone_number'),
                    ],
                    'customer_info' => [
                        'id' => $order->customerInfo->id,
                        'address' => $order->customerInfo->address ?? null,
                    ],
                    'livreur' => $order->livreur ? [
                        'id' => $order->livreur->id,
                        'name' => $order->livreur->first_name . ' ' . $order->livreur->last_name,
                    ] : null,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            // Get unique livreurs for the filter dropdown
            $livreurs = Livreur::select('id', 'first_name','last_name')->orderBy('first_name')->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'orders' => $formattedOrders,
                    'pagination' => [
                        'total' => $orders->total(),
                        'per_page' => $orders->perPage(),
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                    ],
                    'filters' => [
                        'livreurs' => $livreurs,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching orders: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des commandes.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
