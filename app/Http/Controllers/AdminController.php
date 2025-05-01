<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            $query = Order::with(['client.user', 'livreur.user', 'customerInfo'])
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
                        'name' => $order->client->nom . ' ' . $order->client->prenom,
                        'adresse' => $order->client->adresse,
                        'user' => User::find($order->client->id),
                    ],
                    'customer_info' => [
                        'id' => $order->customerInfo->id,
                        'address' => $order->customerInfo->address ?? null,
                        'phone_number' => $order->customerInfo->phone_number ?? null,
                    ],
                    'livreur' => $order->livreur ? [
                        'id' => $order->livreur->id,
                        'name' => $order->livreur->first_name . ' ' . $order->livreur->last_name,
                        'user' => User::find($order->livreur->id),
                    ] : null,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            // Get unique livreurs for the filter dropdown
            $livreurs = Livreur::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();

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

    public function getOrder($id): JsonResponse
    {
        try {
            // Fetch the order with its relationships
            $order = Order::with([
                'client',              // Get client information
                'customerInfo',        // Get customer shipping information
                'customerInfo.zoneGeographic', // Get zone information
                'livreur',            // Get delivery person information
                'livreur.user'         // Get user info for the delivery person
            ])->findOrFail($id);

            // Format the response data
            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'description' => $order->description,
                'designation_product' => $order->designation_product,
                'product_width' => $order->product_width,
                'product_height' => $order->product_height,
                'weight' => $order->weight,
                'collection_date' => $order->collection_date,
                'status' => $order->status,
                'amount' => (float) $order->amount,
                'delivery_date' => $order->delivery_date,
                // Client information
                'client' => [
                    'id' => $order->client->id,
                    'nom' => $order->client->nom,
                    'prenom' => $order->client->prenom,
                    'boutique' => $order->client->boutique,
                    'cin' => $order->client->cin,
                    'banque' => $order->client->banque,
                    'rib' => $order->client->rib,
                    'ville' => $order->client->ville,
                    'adresse' => $order->client->adresse,
                ],

                // Customer delivery information
                'customer_info' => [
                    'id' => $order->customerInfo->id,
                    'full_name' => $order->customerInfo->full_name,
                    'phone_number' => $order->customerInfo->phone_number,
                    'address' => $order->customerInfo->address,
                    'city' => $order->customerInfo->city,
                    'zone_geographic' => [
                        'id' => $order->customerInfo->zoneGeographic->id,
                        'name' => $order->customerInfo->zoneGeographic->name, // Assuming you have a name field in the zone_geographics table
                    ],
                ],

                // Delivery person information (if assigned)
                'livreur' => $order->livreur ? [
                    'id' => $order->livreur->id,
                    'first_name' => $order->livreur->first_name,
                    'last_name' => $order->livreur->last_name,
                    'cin' => $order->livreur->cin,
                    'disponible' => (bool) $order->livreur->disponible,
                    'adresse' => $order->livreur->adresse,
                    'nomber_livraisons' => $order->livreur->nomber_livraisons,
                    'user' => [
                        'id' => $order->livreur->user->id,
                        'email' => $order->livreur->user->email,
                    ],
                ] : null,

                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Order not found',
                'message' => 'The requested order could not be found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'An error occurred while fetching the order details',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
