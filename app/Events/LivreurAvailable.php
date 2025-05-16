<?php

namespace App\Events;

use App\Models\Livreur;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LivreurAvailable implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // In your event class
    public $queue = 'notifications';
    public $livreur;

    /**
     * Create a new event instance.
     */
    public function __construct(Livreur $livreur)
    {
        $this->livreur = $livreur;
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
            'message' => 'Livreur ' . $this->livreur->first_name . ' is now available',
            'livreur_id' => $this->livreur->id,
            'livreur_name' => $this->livreur->first_name,
            'timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'livreur.available';
    }
}
