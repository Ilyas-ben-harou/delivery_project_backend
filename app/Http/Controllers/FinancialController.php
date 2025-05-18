<?php

namespace App\Http\Controllers;

use App\Models\ZoneGeographic;
use App\Models\Order;
use App\Models\Livreur;
use App\Models\CustomerInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FinancialController extends Controller
{
    /**
     * Display financial dashboard data
     * Now calculates earnings based on delivery zone prices
     */
    public function dashboard(Request $request)
    {
        try {
            // Default to current month if no date range is provided
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

            if (is_string($startDate)) {
                $startDate = Carbon::parse($startDate);
            }
            
            if (is_string($endDate)) {
                $endDate = Carbon::parse($endDate);
            }

            // Get total earnings based on zone pricing for delivered orders
            $totalEarnings = Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->where('orders.status', 'delivered')
                ->join('customer_infos', 'orders.customer_info_id', '=', 'customer_infos.id')
                ->join('zone_geographics', 'customer_infos.zone_geographic_id', '=', 'zone_geographics.id')
                ->sum('zone_geographics.price');

            // Get completed orders by status
            $ordersByStatus = Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            // Get earnings by day for the period based on zone pricing
            $earningsByDay = Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->where('orders.status', 'delivered')
                ->join('customer_infos', 'orders.customer_info_id', '=', 'customer_infos.id')
                ->join('zone_geographics', 'customer_infos.zone_geographic_id', '=', 'zone_geographics.id')
                ->select(
                    DB::raw('DATE(orders.created_at) as date'),
                    DB::raw('SUM(zone_geographics.price) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Calculate distributor commissions (assuming 10% commission - adjust as needed)
            $commissionPercentage = 0.10; // 10% commission

            // Get top distributors by earnings with their commission
            $topDistributors = Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->where('orders.status', 'delivered')
                ->whereNotNull('orders.livreur_id')
                ->join('customer_infos', 'orders.customer_info_id', '=', 'customer_infos.id')
                ->join('zone_geographics', 'customer_infos.zone_geographic_id', '=', 'zone_geographics.id')
                ->select(
                    'orders.livreur_id',
                    DB::raw('SUM(zone_geographics.price) as total_revenue'), 
                    DB::raw('SUM(zone_geographics.price * ' . $commissionPercentage . ') as total_commission'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('orders.livreur_id')
                ->orderBy('total_commission', 'desc')
                ->limit(5)
                ->with('livreur')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->livreur_id,
                        'name' => $order->livreur ? $order->livreur->first_name . ' ' . $order->livreur->last_name : 'Unknown',
                        'total_revenue' => $order->total_revenue,
                        'total_commission' => round($order->total_commission, 2),
                        'count' => $order->count
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_earnings' => $totalEarnings,
                    'pending_payments' => 0, // Placeholder for pending payments
                    'orders_by_status' => $ordersByStatus,
                    'earnings_by_day' => $earningsByDay,
                    'top_distributors' => $topDistributors,
                    'period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in financial dashboard: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve financial data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get pricing for all geographic zones
     */
    public function getPricing()
    {
        try {
            $pricing = ZoneGeographic::orderBy('city')->orderBy('secteur')->get();

            return response()->json([
                'status' => 'success',
                'data' => $pricing
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching pricing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve pricing data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create or update zone pricing
     */
    public function updatePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'sometimes|exists:zone_geographics,id',
            'city' => 'required_without:id|string|max:255',
            'secteur' => 'required_without:id|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->has('id')) {
                // Update existing zone
                $zone = ZoneGeographic::findOrFail($request->id);
                $zone->price = $request->price;
                
                if ($request->has('city')) {
                    $zone->city = $request->city;
                }
                
                if ($request->has('secteur')) {
                    $zone->secteur = $request->secteur;
                }
                
                $zone->save();
            } else {
                // Create new zone
                $zone = ZoneGeographic::create([
                    'city' => $request->city,
                    'secteur' => $request->secteur,
                    'price' => $request->price
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Zone pricing updated successfully',
                'data' => $zone
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating zone pricing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update zone pricing',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete zone pricing
     */
    public function deletePricing($id)
    {
        try {
            $zone = ZoneGeographic::findOrFail($id);
            
            // Check if this zone is used by any customer info records
            $customerCount = $zone->customerInfos()->count();
            if ($customerCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot delete zone: it's currently used by {$customerCount} customers"
                ], 422);
            }
            
            $zone->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Zone pricing deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting zone pricing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete zone pricing',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get payment status for distributors with commission calculation
     */
    public function getDistributorPayments(Request $request)
    {
        try {
            // Filter by date range if provided
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // Default commission percentage - adjust as needed
            $commissionPercentage = 0.10; // 10% commission
            
            $query = Livreur::with(['orders' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered');
                
                if ($startDate && $endDate) {
                    $q->whereBetween('orders.delivery_date', [$startDate, $endDate]);
                }
                
                // Load the customer info and zone data for each order
                $q->with(['customerInfo.zoneGeographic']);
            }]);
            
            // Search by name if provided
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            }
            
            $distributors = $query->get();
            
            $formattedDistributors = $distributors->map(function ($distributor) use ($commissionPercentage) {
                $totalRevenue = 0;
                $orderCount = $distributor->orders->count();
                
                // Calculate total revenue from zone pricing
                foreach ($distributor->orders as $order) {
                    if ($order->customerInfo && $order->customerInfo->zoneGeographic) {
                        $totalRevenue += $order->customerInfo->zoneGeographic->price;
                    }
                }
                
                // Calculate commission
                $commission = $totalRevenue * $commissionPercentage;
                
                return [
                    'id' => $distributor->id,
                    'name' => $distributor->first_name . ' ' . $distributor->last_name,
                    'total_revenue' => $totalRevenue,
                    'commission' => round($commission, 2),
                    'order_count' => $orderCount,
                    'paid' => false, // This would need to be linked to a payments table if implementing full payment tracking
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedDistributors
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching distributor payments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve distributor payment data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Generate financial report with zone-based earnings
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:daily,weekly,monthly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $reportType = $request->report_type;
            
            // Default commission percentage
            $commissionPercentage = 0.10; // 10% commission
            
            // Group format based on report type
            $groupFormat = 'Y-m-d'; // Default daily format
            if ($reportType === 'weekly') {
                $groupFormat = 'Y-W'; // Year and week number
            } else if ($reportType === 'monthly') {
                $groupFormat = 'Y-m'; // Year and month
            }
            
            // Get orders grouped by the specified time period with zone-based revenue
            $reportData = Order::whereBetween('orders.created_at', [$startDate, $endDate])
                ->join('customer_infos', 'orders.customer_info_id', '=', 'customer_infos.id')
                ->join('zone_geographics', 'customer_infos.zone_geographic_id', '=', 'zone_geographics.id')
                ->select(
                    DB::raw("DATE_FORMAT(orders.created_at, '{$groupFormat}') as period"),
                    DB::raw('SUM(zone_geographics.price) as total_revenue'),
                    DB::raw('SUM(zone_geographics.price * ' . $commissionPercentage . ') as total_commission'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw("SUM(CASE WHEN orders.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count"),
                    DB::raw("SUM(CASE WHEN orders.status = 'failed' THEN 1 ELSE 0 END) as failed_count")
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();
                
            return response()->json([
                'status' => 'success',
                'data' => [
                    'report_type' => $reportType,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'commission_percentage' => $commissionPercentage * 100 . '%',
                    'report_data' => $reportData
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating financial report: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate financial report',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}