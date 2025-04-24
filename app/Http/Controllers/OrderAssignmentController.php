<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Livreur;
use App\Models\ZoneGeographic;
use App\Services\OrderAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderAssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(OrderAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
        $this->middleware('auth:api');
    }

    public function index()
    {
        $unassignedOrders = Order::whereNull('livreur_id')
            ->whereNotNull('customer_info_id')
            ->orderBy('collection_date')
            ->with('customerInfo')
            ->paginate(10);

        $zones = ZoneGeographic::withCount(['livreurs' => function ($query) {
            $query->where('disponible', true);
        }])->get();

        return response()->json([
            'unassignedOrders' => $unassignedOrders,
            'zones' => $zones
        ]);
    }

    public function assignOrder(Order $order)
    {
        $result = $this->assignmentService->assignOrderToLivreur($order);

        if ($result) {
            // Get the updated order with livreur relationship
            $updatedOrder = Order::with('livreur')->find($order->id);

            return response()->json([
                'success' => true,
                'message' => "Order #{$order->order_number} has been successfully assigned.",
                'order' => $updatedOrder
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Could not assign order #{$order->order_number}. No available delivery personnel in the customer's zone."
            ], 422);
        }
    }

    public function manualAssign(Request $request, Order $order)
    {
        $request->validate([
            'livreur_id' => 'required|exists:livreurs,id'
        ]);

        $livreur = Livreur::find($request->livreur_id);

        if (!$livreur->disponible) {
            return response()->json([
                'success' => false,
                'message' => "Selected delivery person is not available."
            ], 422);
        }

        $order->livreur_id = $livreur->id;
        $order->status = 'assigned';
        $order->save();

        // Update delivery count
        $livreur->nomber_livraisons += 1;
        $livreur->save();

        // Get the updated order with livreur relationship
        $updatedOrder = Order::with('livreur')->find($order->id);

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->order_number} has been manually assigned to {$livreur->first_name} {$livreur->last_name}.",
            'order' => $updatedOrder
        ]);
    }

    public function batchAssign()
    {
        $stats = $this->assignmentService->batchAssignOrders();

        return response()->json([
            'success' => true,
            'message' => "Assignment complete: {$stats['assigned']} orders assigned, {$stats['failed']} orders couldn't be assigned.",
            'stats' => $stats
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'max_deliveries' => 'required|integer|min:1'
        ]);

        $updatedCount = $this->assignmentService->updateLivreurAvailability($request->max_deliveries);

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} delivery personnel had their availability status updated.",
            'updated_count' => $updatedCount
        ]);
    }

    public function reassignOrders(Livreur $livreur)
    {
        if ($livreur->disponible) {
            return response()->json([
                'success' => false,
                'message' => "This delivery person is still available. No need to reassign orders."
            ], 422);
        }

        $stats = $this->assignmentService->reassignOrdersFromUnavailableLivreur($livreur);

        return response()->json([
            'success' => true,
            'message' => "Reassignment complete: {$stats['reassigned']} orders reassigned, {$stats['failed']} orders couldn't be reassigned.",
            'stats' => $stats
        ]);
    }

    public function zoneStatistics()
    {
        $zones = ZoneGeographic::withCount([
            'customerInfos as total_orders' => function ($query) {
                $query->whereHas('orders');
            },
            'customerInfos as assigned_orders' => function ($query) {
                $query->whereHas('orders', function ($orderQuery) {
                    $orderQuery->whereNotNull('livreur_id');
                });
            },
            'livreurs as total_livreurs',
            'livreurs as available_livreurs' => function ($query) {
                $query->where('disponible', true);
            }
        ])->get();

        return response()->json([
            'zones' => $zones
        ]);
    }

    public function getAvailableLivreursByZone(ZoneGeographic $zone)
    {
        // Get livreurs associated with the given zone who are available
        $livreurs = Livreur::whereHas('zones', function ($query) use ($zone) {
            $query->where('zone_geographic_id', $zone->id);
        })->where('disponible', true)
            ->with('user')
            ->get();

        return response()->json([
            'livreurs' => $livreurs
        ]);
    }
}
