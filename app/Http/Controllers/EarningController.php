<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EarningController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $livreur = Livreur::where('user_id', $user->id)->firstOrFail();

        $query = Earning::with(['order.customerInfo'])
            ->where('livreur_id', $livreur->id)
            ->orderBy('created_at', 'desc');

        // Validate and filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();

                $query->whereBetween('created_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'paid'])) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(15));
    }

    public function summary()
    {
        $user = Auth::user();
        $livreur = Livreur::where('user_id', $user->id)->firstOrFail();

        $data = [
            'total_earnings' => Earning::where('livreur_id', $livreur->id)
                ->where('status', 'paid')
                ->sum('commission_amount') ?? 0,

            'pending_earnings' => Earning::where('livreur_id', $livreur->id)
                ->where('status', 'pending')
                ->sum('commission_amount') ?? 0,

            'current_month_earnings' => Earning::where('livreur_id', $livreur->id)
                ->where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('commission_amount') ?? 0,

            'last_month_earnings' => Earning::where('livreur_id', $livreur->id)
                ->where('status', 'paid')
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('commission_amount') ?? 0
        ];

        $data['change_percentage'] = $data['last_month_earnings'] > 0
            ? (($data['current_month_earnings'] - $data['last_month_earnings']) / $data['last_month_earnings'] * 100)
            : 0;

        return response()->json($data);
    }

    // Rest of the controller remains the same


    public function reports(Request $request)
    {
        $user = Auth::user();
        $livreur = Livreur::where('user_id', $user->id)->firstOrFail();

        $groupBy = $request->input('group_by', 'month'); // week, month, year

        $query = Earning::where('livreur_id', $livreur->id)
            ->where('status', 'paid');

        if ($groupBy === 'week') {
            $earnings = $query->selectRaw('
                YEAR(created_at) as year,
                WEEK(created_at) as week,
                SUM(commission_amount) as total_earnings,
                COUNT(*) as delivery_count
            ')
            ->groupBy('year', 'week')
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->get();
        } elseif ($groupBy === 'month') {
            $earnings = $query->selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(commission_amount) as total_earnings,
                COUNT(*) as delivery_count
            ')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
        } else { // year
            $earnings = $query->selectRaw('
                YEAR(created_at) as year,
                SUM(commission_amount) as total_earnings,
                COUNT(*) as delivery_count
            ')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();
        }

        return response()->json($earnings);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $livreur = Livreur::where('user_id', $user->id)->firstOrFail();

        $earnings = Earning::with(['order.customerInfo'])
            ->where('livreur_id', $livreur->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Generate CSV or Excel file
        // You can use Laravel Excel package for more advanced exports
        $fileName = 'earnings_export_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($earnings) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date',
                'Order ID',
                'Customer',
                'Amount',
                'Commission Rate',
                'Commission Amount',
                'Status'
            ]);

            // Data rows
            foreach ($earnings as $earning) {
                fputcsv($file, [
                    $earning->created_at->format('Y-m-d H:i'),
                    $earning->order->order_number,
                    $earning->order->customerInfo->name,
                    $earning->amount,
                    $earning->commission_rate . '%',
                    $earning->commission_amount,
                    ucfirst($earning->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
