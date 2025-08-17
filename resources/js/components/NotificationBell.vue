<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
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
        notifications.value = notifications.value.filter(n => n.id !== notificationId);
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

// Load initial counts on mount
onMounted(() => {
    fetch('/notifications/counts')
        .then(response => response.json())
        .then(data => {
            counts.value = data;
        })
        .catch(error => {
            console.error('Error loading notification counts:', error);
        });
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
        <Button
            variant="ghost"
            size="sm"
            @click="toggleDropdown"
            class="relative p-2"
        >
            <Bell class="h-5 w-5" />
            <Badge
                v-if="hasUnread"
                variant="destructive"
                class="absolute -top-1 -right-1 h-5 w-5 rounded-full p-0 text-xs flex items-center justify-center"
            >
                {{ counts.unread > 99 ? '99+' : counts.unread }}
            </Badge>
        </Button>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute right-0 top-full mt-2 w-80 max-w-sm z-50"
        >
            <Card class="shadow-lg border">
                <CardContent class="p-0">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b">
                        <h3 class="font-semibold text-gray-900">Notificaciones</h3>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="isOpen = false"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Loading state -->
                    <div v-if="isLoading" class="p-4 text-center text-gray-500">
                        Cargando...
                    </div>

                    <!-- Notifications list -->
                    <div v-else-if="notifications.length > 0" class="max-h-96 overflow-y-auto">
                        <div
                            v-for="notification in notifications.slice(0, 5)"
                            :key="notification.id"
                            class="flex items-start space-x-3 p-4 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
                            @click="navigateToNotification(notification)"
                        >
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm">
                                    {{ getNotificationIcon(notification.data.type) }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ notification.data.title || notification.data.message }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </p>
                            </div>

                            <!-- Mark as read button -->
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="markAsRead(notification.id)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <Check class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-8 text-center">
                        <Bell class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-500">No hay notificaciones nuevas</p>
                    </div>

                    <!-- Footer -->
                    <div class="p-3 border-t bg-gray-50">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="goToNotifications"
                            class="w-full text-center"
                        >
                            Ver todas las notificaciones
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>