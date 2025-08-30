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
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            
            $counts = $this->notificationService->getNotificationCounts($user);
            return response()->json($counts);
        } catch (\Exception $e) {
            // Return default counts if service fails
            return response()->json([
                'unread' => 0,
                'total' => 0,
                'urgent' => 0,
            ]);
        }
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

    // API methods for mobile app
    
    /**
     * API: Get all notifications for authenticated user.
     */
    public function apiIndex()
    {
        $user = Auth::user();
        $notifications = $this->notificationService->getAllNotifications($user, 50);

        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * API: Get unread notifications for authenticated user.
     */
    public function apiUnread()
    {
        $user = Auth::user();
        $notifications = $this->notificationService->getUnreadNotifications($user);

        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * API: Get notification counts for authenticated user.
     */
    public function apiCounts()
    {
        $user = Auth::user();
        $counts = $this->notificationService->getNotificationCounts($user);

        return response()->json([
            'success' => true,
            'data' => $counts,
        ]);
    }

    /**
     * API: Mark a notification as read.
     */
    public function apiMarkAsRead(string $id)
    {
        $success = $this->notificationService->markAsRead($id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada',
        ], 404);
    }

    /**
     * API: Mark all notifications as read for authenticated user.
     */
    public function apiMarkAllAsRead()
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "Se marcaron {$count} notificaciones como leídas",
            'count' => $count,
        ]);
    }

    /**
     * API: Delete a notification.
     */
    public function apiDelete(string $id)
    {
        $success = $this->notificationService->deleteNotification($id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada',
        ], 404);
    }
}
