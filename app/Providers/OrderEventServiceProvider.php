<?php

namespace App\Providers;

use App\Models\Order;
use App\Services\OrderAssignmentService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class OrderEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Auto-assign when an order is created
        Order::created(function ($order) {
            if (!$order->livreur_id && $order->customer_info_id) {
                try {
                    $assignmentService = app(OrderAssignmentService::class);
                    $result = $assignmentService->assignOrderToLivreur($order);

                    if ($result) {
                        Log::info("Order #{$order->order_number} automatically assigned on creation");
                    } else {
                        Log::warning("Could not automatically assign Order #{$order->order_number} on creation");
                    }
                } catch (\Exception $e) {
                    Log::error("Error during automatic order assignment: " . $e->getMessage());
                }
            }
        });

        // Update assignments when a livreur's availability changes
        Event::listen('livreur.availability.changed', function ($livreurId, $isAvailable) {
            try {
                $assignmentService = app(OrderAssignmentService::class);

                if (!$isAvailable) {
                    $livreur = \App\Models\Livreur::find($livreurId);
                    if ($livreur) {
                        $stats = $assignmentService->reassignOrdersFromUnavailableLivreur($livreur);
                        foreach ($stats['reassignedOrders'] as $order) {
                            Log::info("Order #{$order->order_number} reassigned from unavailable livreur #{$livreurId}");
                        }
                        Log::info("Total orders reassigned from unavailable livreur #{$livreurId}: {$stats['reassigned']}");
                    }
                } else {
                    // If a livreur becomes available, assign pending orders
                    $stats = $assignmentService->batchAssignOrders();
                    Log::info("Batch assignment after livreur became available. Total assigned: {$stats['assigned']}, Failed: {$stats['failed']}");
                }
            } catch (\Exception $e) {
                Log::error("Error handling livreur availability change: " . $e->getMessage());
            }
        });
    }
}
