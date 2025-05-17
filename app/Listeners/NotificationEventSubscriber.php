<?php

namespace App\Listeners;

use App\Events\LivreurAvailable;
use App\Events\LivreurUnavailable;
use App\Models\Notification;
use Illuminate\Events\Dispatcher;
use Carbon\Carbon;

class NotificationEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            LivreurUnavailable::class,
            [NotificationEventSubscriber::class, 'handleLivreurUnavailable']
        );
        
        $events->listen(
            LivreurAvailable::class,
            [NotificationEventSubscriber::class, 'handleLivreurAvailable']
        );
    }

    /**
     * Handle the livreur unavailable event.
     */
    public function handleLivreurUnavailable(LivreurUnavailable $event): void
    {
        // Check for recent duplicate notifications (within the last 10 seconds)
        $recentNotification = Notification::where('type', 'livreur.unavailable')
            ->where('livreur_id', $event->livreur->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds(10))
            ->first();
            
        if ($recentNotification) {
            // Duplicate found, don't create another notification
            return;
        }
        
        Notification::create([
            'type' => 'livreur.unavailable',
            'livreur_id' => $event->livreur->id,
            'message' => 'Livreur ' . $event->livreur->first_name.' '.$event->livreur->last_name . ' is now unavailable',
            'data' => [
                'reason' => $event->reason,
                'start_date' => $event->unavailablePeriod['start'],
                'end_date' => $event->unavailablePeriod['end'],
            ],
        ]);
    }

    /**
     * Handle the livreur available event.
     */
    public function handleLivreurAvailable(LivreurAvailable $event): void
    {
        // Check for recent duplicate notifications (within the last 10 seconds)
        $recentNotification = Notification::where('type', 'livreur.available')
            ->where('livreur_id', $event->livreur->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds(10))
            ->first();
            
        if ($recentNotification) {
            // Duplicate found, don't create another notification
            return;
        }
        
        Notification::create([
            'type' => 'livreur.available',
            'livreur_id' => $event->livreur->id,
            'message' => 'Livreur ' . $event->livreur->first_name.' '.$event->livreur->last_name . ' is now available',
            'data' => [],
        ]);
    }
}