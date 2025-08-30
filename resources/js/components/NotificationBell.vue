<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Check, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface NotificationData {
    type: string;
    message: string;
    action_url?: string;
    title?: string;
    [key: string]: any;
}

interface Notification {
    id: string;
    type: string;
    data: NotificationData;
    created_at: string;
}

interface NotificationCounts {
    unread: number;
    total: number;
}

const notifications = ref<Notification[]>([]);
const counts = ref<NotificationCounts>({ unread: 0, total: 0 });
const isOpen = ref(false);
const isLoading = ref(false);
const page = usePage();

// Check if we're in tenant context (not central dashboard)
const isTenantContext = computed(() => {
    return !window.location.pathname.startsWith('/dashboard') || page.props.auth?.tenant_id;
});

const hasUnread = computed(() => counts.value.unread > 0);

const loadNotifications = async () => {
    try {
        isLoading.value = true;
        const response = await fetch('/notifications/unread');
        const data = await response.json();

        notifications.value = data.notifications;
        counts.value = data.counts;
    } catch (error) {
        console.error('Error loading notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const markAsRead = async (notificationId: string) => {
    try {
        await fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json',
            },
        });

        // Remove from local state
        notifications.value = notifications.value.filter((n) => n.id !== notificationId);
        counts.value.unread = Math.max(0, counts.value.unread - 1);
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const navigateToNotification = (notification: Notification) => {
    markAsRead(notification.id);
    if (notification.data.action_url) {
        router.visit(notification.data.action_url);
    }
    isOpen.value = false;
};

const toggleDropdown = () => {
    if (!isOpen.value) {
        loadNotifications();
    }
    isOpen.value = !isOpen.value;
};

const goToNotifications = () => {
    router.visit('/notifications');
    isOpen.value = false;
};

const getNotificationIcon = (type: string) => {
    switch (type) {
        case 'maintenance_request_created':
            return '🔧';
        case 'invoice_generated':
            return '📄';
        case 'payment_received':
            return '💰';
        case 'announcement_published':
            return '📢';
        case 'budget_overspend':
            return '⚠️';
        default:
            return '🔔';
    }
};

const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return 'Ahora';
    if (diffInMinutes < 60) return `${diffInMinutes}m`;

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h`;

    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d`;
};

// Load initial counts on mount (only in tenant context)
onMounted(() => {
    if (isTenantContext.value) {
        fetch('/notifications/counts')
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                counts.value = data;
            })
            .catch((error) => {
                console.error('Error loading notification counts:', error);
                // Set default counts on error
                counts.value = {
                    unread: 0,
                    total: 0,
                    urgent: 0,
                };
            });
    }
});

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.notification-dropdown')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="notification-dropdown relative">
        <Button variant="ghost" size="sm" @click="toggleDropdown" class="relative p-2">
            <Bell class="h-5 w-5" />
            <Badge
                v-if="hasUnread"
                variant="destructive"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full p-0 text-xs"
            >
                {{ counts.unread > 99 ? '99+' : counts.unread }}
            </Badge>
        </Button>

        <!-- Dropdown -->
        <div v-if="isOpen" class="absolute top-full right-0 z-50 mt-2 w-80 max-w-sm">
            <Card class="border shadow-lg">
                <CardContent class="p-0">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b p-4">
                        <h3 class="font-semibold text-gray-900">Notificaciones</h3>
                        <Button variant="ghost" size="sm" @click="isOpen = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Loading state -->
                    <div v-if="isLoading" class="p-4 text-center text-gray-500">Cargando...</div>

                    <!-- Notifications list -->
                    <div v-else-if="notifications.length > 0" class="max-h-96 overflow-y-auto">
                        <div
                            v-for="notification in notifications.slice(0, 5)"
                            :key="notification.id"
                            class="flex cursor-pointer items-start space-x-3 border-b p-4 last:border-b-0 hover:bg-gray-50"
                            @click="navigateToNotification(notification)"
                        >
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm">
                                    {{ getNotificationIcon(notification.data.type) }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ notification.data.title || notification.data.message }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </p>
                            </div>

                            <!-- Mark as read button -->
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="markAsRead(notification.id)"
                                class="opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <Check class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-8 text-center">
                        <Bell class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                        <p class="text-sm text-gray-500">No hay notificaciones nuevas</p>
                    </div>

                    <!-- Footer -->
                    <div class="border-t bg-gray-50 p-3">
                        <Button variant="ghost" size="sm" @click="goToNotifications" class="w-full text-center">
                            Ver todas las notificaciones
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
