<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { usePanicAlerts, type SecurityAlert } from '@/composables/usePanicAlerts';
import { AlertTriangle, Bell, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Reactive state
const isMinimized = ref(false);

// Composables
const { hasPermission } = usePermissions();
const { activeAlerts, isLoading, isConnected, init, cleanup } = usePanicAlerts();

// Check if user can view security alerts
const canViewAlerts = computed(
    () => hasPermission('view_security_alerts') || hasPermission('view_panic_alerts') || hasPermission('manage_security_alerts'),
);

// Only show for users with security permissions
const shouldShowBanner = computed(() => canViewAlerts.value && activeAlerts.value.length > 0);

// Get the highest severity alert
const highestSeverityAlert = computed(() => {
    if (activeAlerts.value.length === 0) return null;

    const severityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
    return activeAlerts.value.reduce((highest, current) => {
        const currentSeverity = severityOrder[current.severity] || 0;
        const highestSeverity = severityOrder[highest.severity] || 0;
        return currentSeverity > highestSeverity ? current : highest;
    });
});

// Get banner colors based on severity
const bannerClasses = computed(() => {
    const alert = highestSeverityAlert.value;
    if (!alert) return '';

    const classes = {
        critical: 'bg-red-600 text-white border-red-700',
        high: 'bg-orange-600 text-white border-orange-700',
        medium: 'bg-yellow-600 text-white border-yellow-700',
        low: 'bg-blue-600 text-white border-blue-700',
    };

    return classes[alert.severity] || classes.medium;
});

// Get alert icon
const getAlertIcon = () => {
    const alert = highestSeverityAlert.value;
    if (!alert) return AlertTriangle;

    if (alert.type === 'panic' || alert.severity === 'critical') {
        return AlertTriangle;
    }
    return Bell;
};

// Play alert sound for critical alerts
const playAlertSound = () => {
    try {
        const audio = new Audio();
        audio.src =
            'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUcCUCa4vLEcBwELILN8ti';
        audio.volume = 0.3;
        audio.play().catch(() => {
            // Silent fail if audio can't play
        });
    } catch (error) {
        console.error('Error playing alert sound:', error);
    }
};

// Show browser notification
const showBrowserNotification = (alert: SecurityAlert) => {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('🚨 Alerta de Seguridad Crítica', {
            body: `${alert.user_name} - Apto ${alert.apartment}\n${alert.message}`,
            icon: '/favicon.ico',
            tag: `security-alert-${alert.id}`,
            requireInteraction: true,
        });
    }
};

// Navigate to security alerts page
const viewAlerts = () => {
    window.location.href = '/security/alerts';
};

// Minimize/expand banner
const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
};

// Dismiss banner temporarily (until next reload)
const dismissBanner = () => {
    activeAlerts.value = [];
};

// Format time elapsed
const getTimeElapsed = (createdAt: string) => {
    const now = new Date();
    const created = new Date(createdAt);
    const diff = now.getTime() - created.getTime();

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);

    if (hours > 0) {
        return `hace ${hours}h ${minutes % 60}m`;
    } else {
        return `hace ${minutes}m`;
    }
};

// Request notification permission on mount
onMounted(async () => {
    if ('Notification' in window && Notification.permission === 'default') {
        await Notification.requestPermission();
    }

    // Initialize WebSocket connection and fetch initial alerts
    if (canViewAlerts.value) {
        await init();
    }

    // Watch for new critical alerts to trigger notifications
    // This would typically be handled via a watcher on activeAlerts
    // For simplicity, we'll check on mount
});

// Cleanup on unmount
onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="transform -translate-y-full opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-full opacity-0"
    >
        <div
            v-if="shouldShowBanner"
            :class="['fixed top-0 right-0 left-0 z-50 border-b-2 shadow-lg', bannerClasses, isMinimized ? 'h-12' : 'min-h-16']"
            role="alert"
            aria-live="assertive"
        >
            <div class="container mx-auto px-4">
                <div class="flex h-full items-center justify-between py-2">
                    <!-- Alert Content -->
                    <div class="flex min-w-0 flex-1 items-center space-x-3">
                        <!-- Alert Icon -->
                        <div class="flex-shrink-0">
                            <component
                                :is="getAlertIcon()"
                                :class="['h-6 w-6', highestSeverityAlert?.severity === 'critical' ? 'animate-pulse' : '']"
                            />
                        </div>

                        <!-- Alert Text -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold">
                                    {{ activeAlerts.length }} alerta{{ activeAlerts.length > 1 ? 's' : '' }} de seguridad activa{{
                                        activeAlerts.length > 1 ? 's' : ''
                                    }}
                                </span>
                                <span
                                    v-if="highestSeverityAlert?.severity === 'critical'"
                                    class="animate-pulse rounded bg-white/20 px-2 py-1 text-xs font-medium"
                                >
                                    CRÍTICA
                                </span>
                                <!-- WebSocket connection indicator -->
                                <span
                                    v-if="isConnected"
                                    class="flex items-center text-xs opacity-75"
                                    title="Conectado en tiempo real"
                                >
                                    <span class="mr-1 h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
                                    En vivo
                                </span>
                            </div>

                            <div v-if="!isMinimized && highestSeverityAlert" class="mt-1 truncate text-sm opacity-90">
                                {{ highestSeverityAlert.user_name }} - Apto {{ highestSeverityAlert.apartment }}
                                <span class="mx-2">•</span>
                                {{ getTimeElapsed(highestSeverityAlert.created_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="ml-4 flex flex-shrink-0 items-center space-x-2">
                        <!-- View Alerts Button -->
                        <Button
                            @click="viewAlerts"
                            size="sm"
                            variant="secondary"
                            class="border-white/30 bg-white/20 text-white hover:border-white/50 hover:bg-white/30"
                        >
                            Ver Alertas
                        </Button>

                        <!-- Minimize/Expand Button -->
                        <Button @click="toggleMinimize" size="sm" variant="ghost" class="h-8 w-8 p-1 text-white hover:bg-white/20">
                            <svg
                                :class="['h-4 w-4 transition-transform', isMinimized ? 'rotate-180' : '']"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </Button>

                        <!-- Dismiss Button -->
                        <Button @click="dismissBanner" size="sm" variant="ghost" class="h-8 w-8 p-1 text-white hover:bg-white/20">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Ensure banner appears above all other content */
.z-50 {
    z-index: 9999;
}

/* Animation for critical alerts */
@keyframes pulse-glow {
    0%,
    100% {
        box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
    }
    50% {
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.8);
    }
}

.animate-pulse-glow {
    animation: pulse-glow 2s ease-in-out infinite;
}
</style>
