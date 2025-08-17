<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $this->notificationService->getAllNotifications($user, 50);
        $counts = $this->notificationService->getNotificationCounts($user);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            }),
            'counts' => $counts,
        ]);
    }

    /**
     * Get unread notifications for API.
     */
    public function unread()
    {
        $user = Auth::user();
        $notifications = $this->notificationService->getUnreadNotifications($user);
        $counts = $this->notificationService->getNotificationCounts($user);

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                ];
            }),
            'counts' => $counts,
        ]);
    }

    /**
     * Get notification counts.
     */
    public function counts()
    {
        $user = Auth::user();
        $counts = $this->notificationService->getNotificationCounts($user);

        return response()->json($counts);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id)
    {
        $success = $this->notificationService->markAsRead($id);

        if ($success) {
            return response()->json(['message' => 'Notificación marcada como leída']);
        }

        return response()->json(['message' => 'Notificación no encontrada'], 404);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'message' => "Se marcaron {$count} notificaciones como leídas",
            'count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id)
    {
        $success = $this->notificationService->deleteNotification($id);

        if ($success) {
            return response()->json(['message' => 'Notificación eliminada']);
        }

        return response()->json(['message' => 'Notificación no encontrada'], 404);
    }
}
