<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the admin.
     */
    public function index()
    {
        // Here you would typically check if the user is an admin
        // For now, we'll just return all notifications
        $notifications = Notification::orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($notifications);
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        $notification->read = true;
        $notification->save();
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Notification::where('read', false)
            ->update(['read' => true]);
            
        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }
}
