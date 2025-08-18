<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Bell, BellOff, Check, CheckCheck, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface NotificationData {
    type: string;
    message: string;
    action_url?: string;
    title?: string;
    priority?: string;
    [key: string]: any;
}

interface Notification {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
}

interface Props {
    notifications: Notification[];
    counts: {
        unread: number;
        total: number;
    };
}

const props = defineProps<Props>();

const selectedTab = ref<'all' | 'unread'>('all');

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Notificaciones',
        href: '/notifications',
    },
];

const filteredNotifications = computed(() => {
    if (selectedTab.value === 'unread') {
        return props.notifications.filter((n) => !n.read_at);
    }
    return props.notifications;
});

const markAsRead = async (notificationId: string) => {
    try {
        await fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json',
            },
        });

        router.reload({ only: ['notifications', 'counts'] });
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await fetch('/notifications/mark-all-as-read', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json',
            },
        });

        router.reload({ only: ['notifications', 'counts'] });
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
};

const deleteNotification = async (notificationId: string) => {
    try {
        await fetch(`/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json',
            },
        });

        router.reload({ only: ['notifications', 'counts'] });
    } catch (error) {
        console.error('Error deleting notification:', error);
    }
};

const navigateToAction = (notification: Notification) => {
    if (notification.data.action_url) {
        if (!notification.read_at) {
            markAsRead(notification.id);
        }
        router.visit(notification.data.action_url);
    }
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

const getNotificationTypeLabel = (type: string) => {
    switch (type) {
        case 'maintenance_request_created':
            return 'Solicitud de Mantenimiento';
        case 'invoice_generated':
            return 'Factura Generada';
        case 'payment_received':
            return 'Pago Recibido';
        case 'announcement_published':
            return 'Comunicado Publicado';
        case 'budget_overspend':
            return 'Alerta Presupuestal';
        default:
            return 'Notificación';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Notificaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Bell class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Notificaciones</h1>
                    <Badge v-if="counts.unread > 0" variant="destructive"> {{ counts.unread }} sin leer </Badge>
                </div>
                <div class="flex items-center space-x-3">
                    <Button v-if="counts.unread > 0" variant="outline" @click="markAllAsRead">
                        <CheckCheck class="mr-2 h-4 w-4" />
                        Marcar todas como leídas
                    </Button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex space-x-1 rounded-lg bg-gray-100 p-1">
                <Button :variant="selectedTab === 'all' ? 'default' : 'ghost'" size="sm" @click="selectedTab = 'all'" class="flex-1">
                    Todas ({{ counts.total }})
                </Button>
                <Button :variant="selectedTab === 'unread' ? 'default' : 'ghost'" size="sm" @click="selectedTab = 'unread'" class="flex-1">
                    Sin leer ({{ counts.unread }})
                </Button>
            </div>

            <!-- Notifications List -->
            <div class="space-y-3">
                <div
                    v-for="notification in filteredNotifications"
                    :key="notification.id"
                    class="relative overflow-hidden transition-all duration-200 hover:shadow-md"
                    :class="['rounded-lg border bg-white p-4', notification.read_at ? 'border-gray-200' : 'border-blue-200 bg-blue-50']"
                >
                    <!-- Unread indicator -->
                    <div v-if="!notification.read_at" class="absolute top-0 left-0 h-full w-1 bg-blue-500" />

                    <div class="flex items-start space-x-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-lg">
                                {{ getNotificationIcon(notification.data.type) }}
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <Badge variant="secondary" class="text-xs">
                                            {{ getNotificationTypeLabel(notification.data.type) }}
                                        </Badge>
                                        <span class="text-sm text-gray-500">
                                            {{ formatDate(notification.created_at) }}
                                        </span>
                                    </div>
                                    <h3 class="mt-1 text-sm font-medium text-gray-900">
                                        {{ notification.data.title || notification.data.message }}
                                    </h3>
                                    <p
                                        v-if="notification.data.title && notification.data.message !== notification.data.title"
                                        class="mt-1 text-sm text-gray-600"
                                    >
                                        {{ notification.data.message }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <Button
                                        v-if="!notification.read_at"
                                        variant="ghost"
                                        size="sm"
                                        @click="markAsRead(notification.id)"
                                        title="Marcar como leída"
                                    >
                                        <Check class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="deleteNotification(notification.id)" title="Eliminar">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Action button -->
                            <div v-if="notification.data.action_url" class="mt-3">
                                <Button variant="outline" size="sm" @click="navigateToAction(notification)"> Ver detalles </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredNotifications.length === 0" class="py-12 text-center">
                    <BellOff class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        {{ selectedTab === 'unread' ? 'No hay notificaciones sin leer' : 'No hay notificaciones' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            selectedTab === 'unread' ? 'Todas las notificaciones han sido leídas.' : 'No se encontraron notificaciones en el sistema.'
                        }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
