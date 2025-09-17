<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertTriangle, Clock, MapPin, User, CheckCircle, X } from 'lucide-vue-next';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';

interface SecurityAlert {
    id: string;
    type: 'panic' | 'security' | 'emergency';
    message: string;
    user_name: string;
    apartment: string;
    location?: string;
    created_at: string;
    status: 'triggered' | 'acknowledged' | 'resolved';
    severity: 'low' | 'medium' | 'high' | 'critical';
}

// Page meta
defineOptions({
    layout: 'app/AppSidebarLayout',
});

// Reactive state
const activeAlerts = ref<SecurityAlert[]>([]);
const allAlerts = ref<SecurityAlert[]>([]);
const isLoading = ref(false);
const selectedTab = ref<'active' | 'all'>('active');
const isProcessing = ref<string | null>(null);

// Composables
const { canViewSecurityAlerts, canManageSecurityAlerts, canResolveSecurityIncidents } = usePermissions();
const { addToast } = useToast();

// Computed
const currentAlerts = computed(() => {
    return selectedTab.value === 'active' ? activeAlerts.value : allAlerts.value;
});

const criticalAlertsCount = computed(() => {
    return activeAlerts.value.filter(alert => alert.severity === 'critical').length;
});

// Methods
const fetchActiveAlerts = async () => {
    if (!canViewSecurityAlerts.value) return;

    try {
        isLoading.value = true;
        const response = await fetch('/api/security/alerts/active', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            activeAlerts.value = data.alerts || [];
        }
    } catch (error) {
        console.error('Error fetching active alerts:', error);
        addToast({
            type: 'error',
            title: 'Error',
            message: 'No se pudieron cargar las alertas activas',
        });
    } finally {
        isLoading.value = false;
    }
};

const fetchAllAlerts = async () => {
    if (!canViewSecurityAlerts.value) return;

    try {
        isLoading.value = true;
        const response = await fetch('/api/panic-alerts', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            allAlerts.value = data.alerts || [];
        }
    } catch (error) {
        console.error('Error fetching all alerts:', error);
        addToast({
            type: 'error',
            title: 'Error',
            message: 'No se pudieron cargar las alertas',
        });
    } finally {
        isLoading.value = false;
    }
};

const acknowledgeAlert = async (alertId: string) => {
    if (!canManageSecurityAlerts.value) return;

    try {
        isProcessing.value = alertId;
        const response = await fetch(`/api/panic-alerts/${alertId}/acknowledge`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            addToast({
                type: 'success',
                title: 'Alerta Reconocida',
                message: data.message || 'La alerta ha sido reconocida',
            });

            // Refresh alerts
            await fetchActiveAlerts();
            if (selectedTab.value === 'all') {
                await fetchAllAlerts();
            }
        } else {
            throw new Error('Error al reconocer la alerta');
        }
    } catch (error) {
        console.error('Error acknowledging alert:', error);
        addToast({
            type: 'error',
            title: 'Error',
            message: 'No se pudo reconocer la alerta',
        });
    } finally {
        isProcessing.value = null;
    }
};

const resolveAlert = async (alertId: string) => {
    if (!canResolveSecurityIncidents.value) return;

    try {
        isProcessing.value = alertId;
        const response = await fetch(`/api/panic-alerts/${alertId}/resolve`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            addToast({
                type: 'success',
                title: 'Alerta Resuelta',
                message: data.message || 'La alerta ha sido resuelta',
            });

            // Refresh alerts
            await fetchActiveAlerts();
            if (selectedTab.value === 'all') {
                await fetchAllAlerts();
            }
        } else {
            throw new Error('Error al resolver la alerta');
        }
    } catch (error) {
        console.error('Error resolving alert:', error);
        addToast({
            type: 'error',
            title: 'Error',
            message: 'No se pudo resolver la alerta',
        });
    } finally {
        isProcessing.value = null;
    }
};

const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

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

const getSeverityBadgeVariant = (severity: string) => {
    const variants = {
        critical: 'destructive',
        high: 'destructive',
        medium: 'secondary',
        low: 'outline'
    };
    return variants[severity] || 'outline';
};

const getSeverityText = (severity: string) => {
    const texts = {
        critical: 'Crítica',
        high: 'Alta',
        medium: 'Media',
        low: 'Baja'
    };
    return texts[severity] || severity;
};

const getStatusText = (status: string) => {
    const texts = {
        triggered: 'Activada',
        acknowledged: 'Reconocida',
        resolved: 'Resuelta',
        cancelled: 'Cancelada'
    };
    return texts[status] || status;
};

const switchTab = async (tab: 'active' | 'all') => {
    selectedTab.value = tab;
    if (tab === 'active') {
        await fetchActiveAlerts();
    } else {
        await fetchAllAlerts();
    }
};

// Auto-refresh active alerts every 30 seconds
let refreshInterval: NodeJS.Timeout | null = null;

const startAutoRefresh = () => {
    refreshInterval = setInterval(() => {
        if (selectedTab.value === 'active') {
            fetchActiveAlerts();
        }
    }, 30000);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

// Lifecycle
onMounted(async () => {
    if (canViewSecurityAlerts.value) {
        await fetchActiveAlerts();
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});
</script>

<template>
    <Head title="Alertas de Seguridad" />

    <div class="container mx-auto px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Alertas de Seguridad
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Monitoreo y gestión de alertas de pánico y seguridad
                    </p>
                </div>

                <!-- Critical Alert Count -->
                <div v-if="criticalAlertsCount > 0" class="flex items-center space-x-2">
                    <AlertTriangle class="h-5 w-5 text-red-600" />
                    <Badge variant="destructive" class="animate-pulse">
                        {{ criticalAlertsCount }} alerta{{ criticalAlertsCount > 1 ? 's' : '' }} crítica{{ criticalAlertsCount > 1 ? 's' : '' }}
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Unauthorized Access -->
        <div v-if="!canViewSecurityAlerts" class="text-center py-12">
            <AlertTriangle class="mx-auto h-12 w-12 text-red-600 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Acceso No Autorizado
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                No tienes permisos para ver las alertas de seguridad.
            </p>
        </div>

        <!-- Main Content -->
        <div v-else>
            <!-- Tab Navigation -->
            <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg mb-6">
                <button
                    @click="switchTab('active')"
                    :class="[
                        'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors',
                        selectedTab === 'active'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    Alertas Activas ({{ activeAlerts.length }})
                </button>
                <button
                    @click="switchTab('all')"
                    :class="[
                        'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors',
                        selectedTab === 'all'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    Todas las Alertas
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <Card>
                        <CardHeader>
                            <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </CardHeader>
                    </Card>
                </div>
            </div>

            <!-- No Alerts -->
            <div v-else-if="currentAlerts.length === 0" class="text-center py-12">
                <CheckCircle class="mx-auto h-12 w-12 text-green-600 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ selectedTab === 'active' ? 'No hay alertas activas' : 'No hay alertas registradas' }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ selectedTab === 'active' ? 'Todo está tranquilo en este momento.' : 'No se han registrado alertas de seguridad.' }}
                </p>
            </div>

            <!-- Alerts List -->
            <div v-else class="space-y-4">
                <Card v-for="alert in currentAlerts" :key="alert.id" :class="[
                    'border-l-4',
                    alert.severity === 'critical' ? 'border-l-red-600' :
                    alert.severity === 'high' ? 'border-l-orange-500' :
                    alert.severity === 'medium' ? 'border-l-yellow-500' :
                    'border-l-blue-500'
                ]">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <CardTitle class="text-lg">
                                        🚨 {{ alert.message }}
                                    </CardTitle>
                                    <Badge :variant="getSeverityBadgeVariant(alert.severity)">
                                        {{ getSeverityText(alert.severity) }}
                                    </Badge>
                                    <Badge variant="outline">
                                        {{ getStatusText(alert.status) }}
                                    </Badge>
                                </div>
                                <CardDescription>
                                    Activada {{ getTimeElapsed(alert.created_at) }}
                                </CardDescription>
                            </div>

                            <!-- Action Buttons -->
                            <div v-if="alert.status === 'triggered'" class="flex space-x-2">
                                <Button
                                    v-if="canManageSecurityAlerts"
                                    @click="acknowledgeAlert(alert.id)"
                                    :disabled="isProcessing === alert.id"
                                    size="sm"
                                    variant="outline"
                                >
                                    {{ isProcessing === alert.id ? 'Procesando...' : 'Reconocer' }}
                                </Button>
                                <Button
                                    v-if="canResolveSecurityIncidents"
                                    @click="resolveAlert(alert.id)"
                                    :disabled="isProcessing === alert.id"
                                    size="sm"
                                    variant="default"
                                >
                                    {{ isProcessing === alert.id ? 'Procesando...' : 'Resolver' }}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- User Info -->
                            <div class="flex items-center space-x-2">
                                <User class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="font-medium">{{ alert.user_name }}</p>
                                    <p class="text-sm text-gray-500">{{ alert.apartment }}</p>
                                </div>
                            </div>

                            <!-- Time Info -->
                            <div class="flex items-center space-x-2">
                                <Clock class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm">{{ formatDateTime(alert.created_at) }}</p>
                                    <p class="text-xs text-gray-500">{{ getTimeElapsed(alert.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Location Info -->
                            <div v-if="alert.location" class="flex items-center space-x-2">
                                <MapPin class="h-4 w-4 text-gray-500" />
                                <div>
                                    <p class="text-sm">{{ alert.location }}</p>
                                    <button
                                        @click="() => window.open(`https://www.google.com/maps?q=${alert.location}`, '_blank')"
                                        class="text-xs text-blue-600 hover:text-blue-700"
                                    >
                                        Ver en mapa →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>