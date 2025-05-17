<?php

namespace App\Http\Controllers;

use App\Models\CityPricing;
use App\Models\Order;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FinancialController extends Controller
{
    /**
     * Display financial dashboard data
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

            // Get total earnings for the period
            $totalEarnings = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'delivered')
                ->sum('amount');

            // Get pending payments
            

            // Get completed orders by status
            $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            // Get earnings by day for the period
            $earningsByDay = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'delivered')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get top distributors by earnings
            $topDistributors = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'delivered')
                ->whereNotNull('livreur_id')
                ->select('livreur_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('livreur_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->with('livreur')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->livreur_id,
                        'name' => $order->livreur ? $order->livreur->first_name . ' ' . $order->livreur->last_name : 'Unknown',
                        'total' => $order->total,
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
     * Get pricing for all cities
     */
    public function getPricing()
    {
        try {
            $pricing = CityPricing::orderBy('city')->get();

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
     * Create or update city pricing
     */
    public function updatePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
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
            // Find existing pricing or create new
            $pricing = CityPricing::updateOrCreate(
                ['city' => $request->city],
                ['price' => $request->price]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pricing updated successfully',
                'data' => $pricing
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating pricing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update pricing',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete city pricing
     */
    public function deletePricing($id)
    {
        try {
            $pricing = CityPricing::findOrFail($id);
            $pricing->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pricing deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting pricing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete pricing',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get payment status for distributors
     */
    public function getDistributorPayments(Request $request)
    {
        try {
            // Filter by date range if provided
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $query = Livreur::with(['orders' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered');
                
                if ($startDate && $endDate) {
                    $q->whereBetween('delivery_date', [$startDate, $endDate]);
                }
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
            
            $formattedDistributors = $distributors->map(function ($distributor) {
                $totalEarnings = $distributor->orders->sum('amount');
                $orderCount = $distributor->orders->count();
                
                return [
                    'id' => $distributor->id,
                    'name' => $distributor->first_name . ' ' . $distributor->last_name,
                    'total_earnings' => $totalEarnings,
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
     * Generate financial report
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
            
            // Group format based on report type
            $groupFormat = 'Y-m-d'; // Default daily format
            if ($reportType === 'weekly') {
                $groupFormat = 'Y-W'; // Year and week number
            } else if ($reportType === 'monthly') {
                $groupFormat = 'Y-m'; // Year and month
            }
            
            // Get orders grouped by the specified time period
            $reportData = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period"),
                    DB::raw('SUM(amount) as total_revenue'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count"),
                    DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count")
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