<template>

    <Head :title="tenant.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">{{ tenant.name }}</h1>
                    <div class="flex items-center gap-3">
                        <Badge :variant="getStatusVariant(tenant.status)">
                            {{ getStatusText(tenant.status) }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button @click="router.visit('/tenants')" variant="outline" class="gap-2">
                        <Icon name="arrow-left" class="h-4 w-4" />
                        Volver
                    </Button>
                    <Button v-if="tenant.status === 'active'" @click="impersonateTenant"
                        class="bg-green-600 hover:bg-green-700 gap-2">
                        <Icon name="log-in" class="h-4 w-4" />
                        Ingresar al Tenant
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tenants/${tenant.id}/edit`)" class="gap-2">
                        <Icon name="edit" class="h-4 w-4" />
                        Editar
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Información Básica -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="user" class="h-5 w-5" />
                            Información Básica
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex items-center gap-3">
                                <Icon name="id-card" class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">ID del Tenant</p>
                                    <p class="text-base font-mono">{{ tenant.id }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Icon name="building" class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Nombre</p>
                                    <p class="text-base">{{ tenant.name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Icon name="mail" class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Email</p>
                                    <p class="text-base">{{ tenant.email || 'Sin email configurado' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Icon name="check-circle" class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Estado</p>
                                    <Badge :variant="getStatusVariant(tenant.status)" class="capitalize">
                                        {{ getStatusText(tenant.status) }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Dominios -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="globe" class="h-5 w-5" />
                            Dominios Configurados
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div v-if="tenant.domains.length === 0" class="py-8 text-center">
                            <Icon name="globe" class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p class="text-muted-foreground">No hay dominios configurados</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="domain in tenant.domains" :key="domain.id"
                                class="flex items-center justify-between rounded-lg border p-4">
                                <div class="flex items-center gap-3">
                                    <Icon name="globe" class="h-4 w-4 text-blue-600" />
                                    <div>
                                        <p class="font-medium">{{ domain.domain }}</p>
                                        <p v-if="domain.is_primary" class="text-xs text-green-600">Dominio principal</p>
                                    </div>
                                </div>
                                <Badge v-if="domain.is_primary" variant="default" class="text-xs">
                                    Principal
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Timestamps -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="clock" class="h-5 w-5" />
                        Información del Sistema
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="flex items-center gap-3">
                            <Icon name="calendar" class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de Creación</p>
                                <p class="text-base">{{ tenant.created_at }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Icon name="calendar" class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Última Actualización</p>
                                <p class="text-base">{{ tenant.updated_at }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="settings" class="h-5 w-5" />
                        Acciones del Tenant
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button v-if="tenant.status === 'active'" @click="impersonateTenant"
                            class="bg-green-600 hover:bg-green-700 gap-2">
                            <Icon name="log-in" class="h-4 w-4" />
                            Ingresar al Tenant
                        </Button>
                        <Button v-if="tenant.status === 'active'" variant="outline" @click="suspendTenant"
                            class="text-orange-600 border-orange-200 hover:bg-orange-50 gap-2">
                            <Icon name="pause" class="h-4 w-4" />
                            Suspender Tenant
                        </Button>
                        <Button v-if="tenant.status !== 'active'" variant="outline" @click="activateTenant"
                            class="text-green-600 border-green-200 hover:bg-green-50 gap-2">
                            <Icon name="play" class="h-4 w-4" />
                            Activar Tenant
                        </Button>
                        <Button variant="destructive" @click="deleteTenant" class="gap-2">
                            <Icon name="trash" class="h-4 w-4" />
                            Eliminar Tenant
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Raw Data (for debugging) -->
            <Card v-if="tenant.raw_data" class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="code" class="h-5 w-5" />
                        Datos Raw (Debug)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <pre
                        class="text-xs bg-muted p-4 rounded-md overflow-auto">{{ JSON.stringify(tenant.raw_data, null, 2) }}</pre>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import Icon from '@/components/Icon.vue'

interface Domain {
    id: string
    domain: string
    is_primary: boolean
}

interface Tenant {
    id: string
    name: string
    email: string | null
    status: string
    created_at: string
    updated_at: string
    domains: Domain[]
    raw_data: any
}

interface Props {
    tenant: Tenant
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
    { title: 'Panel Central', href: '/dashboard' },
    { title: 'Gestión de Tenants', href: '/tenants' },
    { title: props.tenant.name, href: `/tenants/${props.tenant.id}` },
]

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'default'
        case 'pending': return 'secondary'
        case 'suspended': return 'destructive'
        default: return 'secondary'
    }
}

const getStatusText = (status: string) => {
    switch (status) {
        case 'active': return 'Activo'
        case 'pending': return 'Pendiente'
        case 'suspended': return 'Suspendido'
        default: return status
    }
}

const impersonateTenant = () => {
    router.post(route('tenant-management.impersonate', props.tenant.id))
}

const suspendTenant = () => {
    if (confirm('¿Estás seguro de que quieres suspender este tenant?')) {
        router.post(route('tenant-management.suspend', props.tenant.id))
    }
}

const activateTenant = () => {
    router.post(route('tenant-management.activate', props.tenant.id))
}

const deleteTenant = () => {
    if (confirm('¿Estás seguro de que quieres eliminar este tenant? Esta acción no se puede deshacer.')) {
        router.delete(route('tenant-management.destroy', props.tenant.id))
    }
}
</script>
