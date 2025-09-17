<template>
    <Head title="Panel Central" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold tracking-tight">Panel Central Tavira</h1>
                        <p class="text-muted-foreground">Bienvenido al panel central. Aquí podrás crear tu conjunto residencial y gestionar tu suscripción.</p>
                    </div>
                </div>
            </div>

            <!-- Welcome Message for New Users -->
            <div v-if="stats.totalTenants === 0" class="mb-8">
                <Card class="border-blue-200 bg-blue-50">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-blue-800">
                            <Icon name="sparkles" class="h-5 w-5" />
                            ¡Bienvenido a Tavira!
                        </CardTitle>
                        <CardDescription class="text-blue-700">
                            Parece que es tu primera vez aquí. Te guiaremos para crear tu primer conjunto residencial y comenzar a gestionar tu propiedad de manera eficiente.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <Button @click="router.visit('/tenants/create')" class="bg-blue-600 hover:bg-blue-700">
                                <Icon name="plus-circle" class="h-4 w-4 mr-2" />
                                Crear Mi Primer Conjunto
                            </Button>
                            <Button variant="outline">
                                <Icon name="play-circle" class="h-4 w-4 mr-2" />
                                Ver Tutorial
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Emergency Panic Alerts Section -->
            <div v-if="activePanicAlerts.length > 0" class="mb-6">
                <Card class="border-red-500 bg-red-50 shadow-lg">
                    <CardHeader class="bg-red-600 text-white rounded-t-lg">
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="alert-triangle" class="h-6 w-6 animate-pulse" />
                            🚨 ALERTAS DE PÁNICO ACTIVAS ({{ activePanicAlerts.length }})
                        </CardTitle>
                        <CardDescription class="text-red-100">
                            EMERGENCIAS QUE REQUIEREN ATENCIÓN INMEDIATA
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-for="alert in activePanicAlerts" :key="alert.id"
                             class="border-b border-red-200 p-4 bg-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                        <Icon name="alert-triangle" class="h-6 w-6 text-red-600 animate-pulse" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-red-900">
                                            {{ alert.user_name || 'Usuario desconocido' }}
                                        </h4>
                                        <p class="text-sm text-red-700">
                                            {{ alert.apartment || 'Apartamento no especificado' }}
                                        </p>
                                        <p class="text-xs text-red-600">
                                            Activada: {{ alert.time_elapsed }} | ID: {{ alert.id }}
                                        </p>
                                        <p v-if="alert.location" class="text-xs text-red-600">
                                            📍 {{ alert.location }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Badge variant="destructive" class="animate-pulse">
                                        {{ alert.status?.toUpperCase() }}
                                    </Badge>
                                    <Button size="sm" variant="outline" @click="resolveAlert(alert.id)">
                                        Marcar como Atendida
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Stats Cards (only show if there are tenants) -->
            <div v-if="stats.totalTenants > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Conjuntos</CardTitle>
                        <Icon name="building" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalTenants }}</div>
                        <p class="text-xs text-muted-foreground">Conjuntos registrados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Conjuntos Activos</CardTitle>
                        <Icon name="check-circle" class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.activeTenants }}</div>
                        <p class="text-xs text-muted-foreground">En funcionamiento</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Conjuntos Pendientes</CardTitle>
                        <Icon name="clock" class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pendingTenants }}</div>
                        <p class="text-xs text-muted-foreground">Por configurar</p>
                    </CardContent>
                </Card>
            </div>

            <!-- My Tenants -->
            <Card>
                <CardHeader>
                    <CardTitle>Mis Conjuntos Residenciales</CardTitle>
                    <CardDescription>
                        <span v-if="recentTenants.length === 0">
                            Aquí aparecerán los conjuntos que hayas creado
                        </span>
                        <span v-else>
                            Tus conjuntos registrados en la plataforma
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-if="recentTenants.length === 0" class="text-center py-8">
                            <Icon name="building" class="h-12 w-12 mx-auto mb-4 opacity-50 text-muted-foreground" />
                            <h3 class="text-lg font-medium text-muted-foreground mb-2">Aún no tienes conjuntos</h3>
                            <p class="text-sm text-muted-foreground mb-4">Crea tu primer conjunto residencial para comenzar</p>
                            <Button @click="router.visit('/tenants/create')" variant="outline">
                                <Icon name="plus-circle" class="h-4 w-4 mr-2" />
                                Crear Conjunto
                            </Button>
                        </div>
                        <div v-else>
                            <div 
                                v-for="tenant in recentTenants" 
                                :key="tenant.id" 
                                class="flex items-center justify-between p-4 border rounded-lg transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <Icon name="building" class="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ tenant.name }}</p>
                                        <p class="text-sm text-muted-foreground">Creado: {{ tenant.created_at }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <Badge :variant="tenant.status === 'active' ? 'default' : 'secondary'">
                                        {{ tenant.status === 'active' ? 'Activo' : 'Pendiente' }}
                                    </Badge>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        @click="router.visit(`/tenants/${tenant.id}`)"
                                    >
                                        <Icon name="settings" class="h-4 w-4 mr-1" />
                                        Gestionar
                                    </Button>
                                    <Button 
                                        v-if="tenant.status === 'active'"
                                        size="sm"
                                        @click="loginToTenant(tenant.id)"
                                    >
                                        <Icon name="log-in" class="h-4 w-4 mr-1" />
                                        Acceder
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Acciones Rápidas</CardTitle>
                    <CardDescription>Gestión de conjuntos y suscripciones</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button 
                            @click="router.visit('/tenants/create')"
                            class="h-auto p-4 flex flex-col items-center space-y-2"
                        >
                            <Icon name="plus-circle" class="h-8 w-8" />
                            <span>Crear Conjunto</span>
                        </Button>
                        <Button 
                            @click="router.visit('/tenants')"
                            variant="outline" 
                            class="h-auto p-4 flex flex-col items-center space-y-2"
                        >
                            <Icon name="building" class="h-8 w-8" />
                            <span>Mis Conjuntos</span>
                        </Button>
                        <Button variant="outline" class="h-auto p-4 flex flex-col items-center space-y-2">
                            <Icon name="credit-card" class="h-8 w-8" />
                            <span>Mi Suscripción</span>
                        </Button>
                        <Button variant="outline" class="h-auto p-4 flex flex-col items-center space-y-2">
                            <Icon name="user-cog" class="h-8 w-8" />
                            <span>Mi Perfil</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import Icon from '@/components/Icon.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'

interface PanicAlert {
    id: string
    alert_id: string
    user?: {
        id: number
        name: string
    }
    apartment?: {
        id: number
        address: string
    }
    location?: {
        lat: number
        lng: number
        string: string
    }
    status: string
    timestamp: string
    time_ago: string
}

interface Props {
    stats: {
        totalTenants: number
        activeTenants: number
        pendingTenants: number
    }
    recentTenants: Array<{
        id: string
        name: string
        status: string
        created_at: string
    }>
    user: any
}

defineProps<Props>()

// Panic alerts state
const activePanicAlerts = ref<PanicAlert[]>([])

const breadcrumbs = [
    {
        title: 'Panel Central',
        href: '/dashboard',
    },
]

const loginToTenant = (tenantId: string) => {
    router.post(`/tenants/${tenantId}/impersonate`)
}

// Handle panic alert resolution
const resolveAlert = (alertId: string) => {
    console.log('Resolving panic alert:', alertId)

    // Remove from active alerts
    activePanicAlerts.value = activePanicAlerts.value.filter(alert => alert.id !== alertId)

    // Show success notification with toast
    // TODO: Import and use toast from shadcn/vue
    console.log(`Alerta ${alertId} marcada como atendida`)
}

// Listen for panic alerts
const setupPanicAlertListener = () => {
    const checkForNewAlerts = () => {
        try {
            const alertsKey = 'panic_alerts_demo'
            const storedAlerts = JSON.parse(localStorage.getItem(alertsKey) || '[]')

            // Convert stored alerts to the format we need
            const newAlerts = storedAlerts.map((alert: any) => ({
                id: alert.alertId,
                alert_id: alert.alertId,
                user: {
                    id: 1,
                    name: 'Usuario Demo'
                },
                apartment: {
                    id: 1,
                    address: 'Apartamento Demo'
                },
                location: alert.location,
                status: alert.status || 'triggered',
                timestamp: alert.timestamp,
                time_ago: new Date(alert.timestamp).toLocaleString()
            }))

            // Only add new alerts that aren't already in our active list
            newAlerts.forEach((newAlert: PanicAlert) => {
                const exists = activePanicAlerts.value.some(existing => existing.alert_id === newAlert.alert_id)
                if (!exists) {
                    activePanicAlerts.value.push(newAlert)

                    // Show browser notification
                    if (Notification.permission === 'granted') {
                        new Notification('🚨 ALERTA DE PÁNICO', {
                            body: `Emergencia activada por ${newAlert.user?.name}`,
                            icon: '/favicon.ico',
                            tag: 'panic-alert',
                            requireInteraction: true
                        })
                    }
                }
            })

        } catch (error) {
            console.error('Error checking for panic alerts:', error)
        }
    }

    // Check every 2 seconds for new alerts
    const interval = setInterval(checkForNewAlerts, 2000)
    return () => clearInterval(interval)
}

// Setup on mount
onMounted(() => {
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission()
    }

    // Setup panic alert listener
    const cleanup = setupPanicAlertListener()
    onUnmounted(cleanup)
})
</script>