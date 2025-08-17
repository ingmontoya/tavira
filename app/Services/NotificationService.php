<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send notification to specific user
     */
    public function notifyUser(User $user, $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Send notification to multiple users
     */
    public function notifyUsers(Collection $users, $notification): void
    {
        $users->each(function ($user) use ($notification) {
            $user->notify($notification);
        });
    }

    /**
     * Send notification to administrative users
     */
    public function notifyAdministrative($notification): void
    {
        $adminUsers = User::administrative()->active()->get();
        $this->notifyUsers($adminUsers, $notification);
    }

    /**
     * Send notification to all users with specific role
     */
    public function notifyByRole(string $role, $notification): void
    {
        $users = User::role($role)->active()->get();
        $this->notifyUsers($users, $notification);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return $user->unreadNotifications;
    }

    /**
     * Get all notifications for user
     */
    public function getAllNotifications(User $user, int $limit = 50): Collection
    {
        return $user->notifications()->limit($limit)->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId): bool
    {
        $notification = DatabaseNotification::find($notificationId);

        if ($notification) {
            $notification->markAsRead();

            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications->markAsRead();
    }

    /**
     * Delete notification
     */
    public function deleteNotification(string $notificationId): bool
    {
        $notification = DatabaseNotification::find($notificationId);

        if ($notification) {
            $notification->delete();

            return true;
        }

        return false;
    }

    /**
     * Get notification counts for user
     */
    public function getNotificationCounts(User $user): array
    {
        return [
            'unread' => $user->unreadNotifications->count(),
            'total' => $user->notifications->count(),
        ];
    }
}
