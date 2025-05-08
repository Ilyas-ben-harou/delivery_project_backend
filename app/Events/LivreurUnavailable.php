<?php

namespace App\Events;

use App\Models\Livreur;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LivreurUnavailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $livreur;
    public $reason;
    public $unavailablePeriod;

    /**
     * Create a new event instance.
     */
    public function __construct(Livreur $livreur, string $reason, array $unavailablePeriod)
    {
        $this->livreur = $livreur;
        $this->reason = $reason;
        $this->unavailablePeriod = $unavailablePeriod;
        
        // Don't broadcast all livreur data for security
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
            new PrivateChannel('livreur.' . $this->livreur->id)
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Livreur ' . $this->livreur->user->name . ' is now unavailable',
            'livreur_id' => $this->livreur->id,
            'livreur_name' => $this->livreur->user->name,
            'reason' => $this->reason,
            'start_date' => $this->unavailablePeriod['start'],
            'end_date' => $this->unavailablePeriod['end'],
            'timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'livreur.unavailable';
    }
}