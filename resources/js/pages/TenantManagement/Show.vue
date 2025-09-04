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
                    <Button v-if="isSuperAdmin && tenant.status === 'active'" @click="impersonateTenant"
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

            <!-- Password Information Alert -->
            <div v-if="$page.props.flash.show_password_info && $page.props.flash.temp_password" 
                class="mb-8 rounded-lg bg-amber-50 border border-amber-200 p-6">
                <div class="flex items-start gap-4">
                    <Icon name="key" class="h-6 w-6 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-amber-800 mb-2">
                            Credenciales del Administrador del Tenant
                        </h3>
                        <div class="space-y-3 text-sm">
                            <p class="text-amber-700">
                                El usuario administrador ha sido creado con las siguientes credenciales:
                            </p>
                            <div class="bg-white rounded-md p-4 border border-amber-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="font-medium text-gray-700">Email:</p>
                                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">{{ tenant.email || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">Password Temporal:</p>
                                        <div class="flex items-center gap-2">
                                            <p class="font-mono text-sm bg-gray-50 p-2 rounded flex-1">{{ $page.props.flash.temp_password }}</p>
                                            <Button @click="copyPassword" variant="outline" size="sm" class="gap-1">
                                                <Icon name="copy" class="h-4 w-4" />
                                                Copiar
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <Icon name="alert-triangle" class="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" />
                                <p class="text-amber-700">
                                    <strong>Importante:</strong> El usuario será obligado a cambiar esta contraseña en su primer inicio de sesión.
                                    Guarda estas credenciales en un lugar seguro hasta que el administrador del tenant las actualice.
                                </p>
                            </div>
                        </div>
                    </div>
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

            <!-- Actions - Only visible for superadmin -->
            <Card v-if="isSuperAdmin" class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="settings" class="h-5 w-5" />
                        Acciones del Tenant
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Active tenant actions -->
                        <Button v-if="tenant.status === 'active'" @click="impersonateTenant"
                            class="bg-green-600 hover:bg-green-700 gap-2">
                            <Icon name="log-in" class="h-4 w-4" />
                            Ingresar al Tenant
                        </Button>
                        
                        <AlertDialog v-model:open="suspendDialogOpen">
                            <AlertDialogTrigger as-child>
                                <Button v-if="tenant.status === 'active'" variant="outline" 
                                    class="text-orange-600 border-orange-200 hover:bg-orange-50 gap-2">
                                    <Icon name="pause" class="h-4 w-4" />
                                    Suspender Tenant
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Suspender Tenant</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        ¿Estás seguro de que quieres suspender el tenant "{{ tenant.name }}"?<br><br>
                                        Los usuarios del tenant no podrán acceder hasta que sea reactivado.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                    <AlertDialogAction @click="suspendTenant" class="bg-orange-600 hover:bg-orange-700">
                                        Suspender Tenant
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                        
                        <!-- Suspended tenant actions -->
                        <AlertDialog v-model:open="activateDialogOpen">
                            <AlertDialogTrigger as-child>
                                <Button v-if="tenant.status === 'suspended'" variant="outline" 
                                    class="text-green-600 border-green-200 hover:bg-green-50 gap-2">
                                    <Icon name="play" class="h-4 w-4" />
                                    Reactivar Tenant
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Reactivar Tenant</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        ¿Estás seguro de que quieres reactivar el tenant "{{ tenant.name }}"?<br><br>
                                        Los usuarios podrán acceder nuevamente al tenant.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                    <AlertDialogAction @click="activateTenant" class="bg-green-600 hover:bg-green-700">
                                        Reactivar Tenant
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                        
                        <!-- Danger zone -->
                        <AlertDialog v-model:open="deleteDialogOpen">
                            <AlertDialogTrigger as-child>
                                <Button variant="destructive" class="gap-2">
                                    <Icon name="trash" class="h-4 w-4" />
                                    Eliminar Tenant
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Eliminar Tenant</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        ¿Estás seguro de que quieres eliminar el tenant "{{ tenant.name }}"?<br><br>
                                        <strong class="text-red-600">Esta acción no se puede deshacer</strong> y se eliminarán todos los datos asociados.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                    <AlertDialogAction @click="deleteTenant" class="bg-red-600 hover:bg-red-700">
                                        Eliminar Tenant
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
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

        <!-- User Selection Dialog -->
        <div v-if="showUserDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showUserDialog = false">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Seleccionar Usuario para Impersonar</h3>
                    <Button variant="ghost" @click="showUserDialog = false" class="p-1">
                        <Icon name="x" class="h-4 w-4" />
                    </Button>
                </div>
                
                <div v-if="loadingUsers" class="text-center py-8">
                    <p>Cargando usuarios...</p>
                </div>
                
                <div v-else-if="tenantUsers.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No se encontraron usuarios en este tenant</p>
                </div>
                
                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                    <div v-for="user in tenantUsers" :key="user.id" 
                         class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                         @click="impersonateUser(user.id)">
                        <div class="flex items-center gap-3">
                            <Icon name="user" class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                            </div>
                        </div>
                        <Icon name="log-in" class="h-4 w-4 text-green-600" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import Icon from '@/components/Icon.vue'
import axios from 'axios'

interface Domain {
    id: string
    domain: string
    is_primary: boolean
}

interface TenantUser {
    id: number
    name: string
    email: string
    created_at: string
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

// Access current user data
const page = usePage()
const user = computed(() => page.props.auth?.user)
const isSuperAdmin = computed(() => user.value?.roles?.some((role: any) => role.name === 'superadmin'))

// Tenant users management
const tenantUsers = ref<TenantUser[]>([])
const loadingUsers = ref(false)
const showUserDialog = ref(false)

// Dialog states
const deleteDialogOpen = ref(false)
const suspendDialogOpen = ref(false)
const activateDialogOpen = ref(false)

// Breadcrumbs
const breadcrumbs = [
    { title: 'Panel Central', href: '/dashboard' },
    { title: 'Gestión de Tenants', href: '/tenants' },
    { title: props.tenant.name, href: `/tenants/${props.tenant.id}` },
]

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'default'
        case 'suspended': return 'destructive'
        default: return 'secondary'
    }
}

const getStatusText = (status: string) => {
    switch (status) {
        case 'active': return 'Activo'
        case 'suspended': return 'Suspendido'
        default: return status
    }
}

const loadTenantUsers = async () => {
    if (props.tenant.status !== 'active') return
    
    loadingUsers.value = true
    try {
        const url = `/tenants/${props.tenant.id}/users`
        const response = await axios.get(url)
        tenantUsers.value = response.data.users || []
        showUserDialog.value = true
    } catch (error) {
        console.error('Error loading tenant users:', error)
        console.error('Error al cargar los usuarios del tenant')
    } finally {
        loadingUsers.value = false
    }
}

const impersonateUser = async (userId: number) => {
    try {
        const url = `/tenants/${props.tenant.id}/impersonate`
        const response = await axios.post(url, {
            user_id: userId,
            redirect_url: '/dashboard'
        })
        
        if (response.data.success) {
            // Open tenant URL with impersonation token
            window.open(response.data.url, '_blank')
            showUserDialog.value = false
        } else {
            console.error('Error al generar token de impersonación')
        }
    } catch (error: any) {
        console.error('Error impersonating user:', error)
        const errorMessage = error.response?.data?.error || 'Error al impersonar usuario'
        console.error(errorMessage)
    }
}

const impersonateTenant = () => {
    loadTenantUsers()
}

const suspendTenant = () => {
    suspendDialogOpen.value = false
    router.post(route('tenant-management.suspend', props.tenant.id))
}

const activateTenant = () => {
    activateDialogOpen.value = false
    router.post(route('tenant-management.activate', props.tenant.id))
}

const deleteTenant = () => {
    deleteDialogOpen.value = false
    router.delete(route('tenant-management.destroy', props.tenant.id))
}

const copyPassword = () => {
    const password = page.props.flash.temp_password
    if (password) {
        navigator.clipboard.writeText(password).then(() => {
            console.log('Password copied to clipboard')
        })
    }
}
</script>
