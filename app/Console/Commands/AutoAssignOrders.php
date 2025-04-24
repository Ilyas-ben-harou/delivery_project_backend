<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Livreur;
use App\Services\OrderAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoAssignOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-assign {--force : Force reassignment of already assigned orders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically assign unassigned orders to available delivery personnel based on geographic zones';

    /**
     * The order assignment service.
     *
     * @var \App\Services\OrderAssignmentService
     */
    protected $assignmentService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\OrderAssignmentService $assignmentService
     * @return void
     */
    public function __construct(OrderAssignmentService $assignmentService)
    {
        parent::__construct();
        $this->assignmentService = $assignmentService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting automatic order assignment process...');

        // First update livreur availability status
        $updatedCount = $this->assignmentService->updateLivreurAvailability();
        $this->info("{$updatedCount} delivery personnel had their availability status updated.");

        // Handle force reassignment if requested
        if ($this->option('force')) {
            $this->info('Force option detected. Clearing all existing assignments...');
            $clearedCount = Order::whereNotNull('livreur_id')
                ->whereIn('status', ['assigned', 'pending'])
                ->update(['livreur_id' => null]);
            $this->info("{$clearedCount} existing assignments were cleared for reassignment.");
        }

        // Get all unassigned orders
        $orders = Order::whereNull('livreur_id')->whereIn('status', ['pending', 'unassigned'])->get();

        // Loop through the unassigned orders
        $assignedCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            // Find livreurs who are available and belong to the geographic zone of the order
            $livreurs = Livreur::where('disponible', true)
                ->whereHas('zones', function ($query) use ($order) {
                    $query->where('id', $order->zone_geographic_id);
                })
                ->get();

            // If there are available livreurs, assign the order
            if ($livreurs->isNotEmpty()) {
                $livreur = $livreurs->first(); // Select the first available livreur (can be optimized)
                $order->livreur_id = $livreur->id;
                $order->status = 'assigned'; // Update order status
                $order->save();

                $assignedCount++;
            } else {
                $failedCount++;
            }
        }

        $this->info("Assignment complete: {$assignedCount} orders assigned, {$failedCount} orders couldn't be assigned.");

        Log::info('Auto assignment completed', [
            'assigned' => $assignedCount,
            'failed' => $failedCount,
        ]);

        return Command::SUCCESS;
    }
}
