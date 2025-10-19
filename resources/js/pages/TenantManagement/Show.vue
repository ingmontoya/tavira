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
                    <Button
                        v-if="isSuperAdmin && tenant.status === 'active'"
                        @click="impersonateTenant"
                        class="gap-2 bg-green-600 hover:bg-green-700"
                    >
                        <Icon name="log-in" class="h-4 w-4" />
                        Ingresar al Tenant
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tenants/${tenant.id}/edit`)" class="gap-2">
                        <Icon name="edit" class="h-4 w-4" />
                        Editar
                    </Button>
                </div>
            </div>

            <!-- Email Sent Confirmation Alert -->
            <div v-if="$page.props.flash.email_sent" class="mb-8 rounded-lg border border-green-200 bg-green-50 p-6">
                <div class="flex items-start gap-4">
                    <Icon name="mail" class="mt-0.5 h-6 w-6 flex-shrink-0 text-green-600" />
                    <div class="flex-1">
                        <h3 class="mb-2 text-lg font-semibold text-green-800">✅ Tenant Creado Exitosamente</h3>
                        <div class="space-y-3 text-sm">
                            <p class="text-green-700">Se han enviado las credenciales de acceso por correo electrónico.</p>
                            <!-- Temporary password display for debugging -->
                            <div v-if="$page.props.flash.temp_password" class="rounded-md border border-amber-200 bg-amber-50 p-4">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <Icon name="key" class="h-4 w-4 text-amber-600" />
                                        <span class="font-medium text-amber-800">Credenciales temporales:</span>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-medium text-gray-600">Email:</p>
                                            <p class="rounded border bg-white p-2 font-mono text-sm">{{ tenant.email || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600">Password:</p>
                                            <p class="rounded border bg-white p-2 font-mono text-sm">{{ $page.props.flash.temp_password }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-2">
                                        <Icon name="alert-triangle" class="h-4 w-4 text-amber-600" />
                                        <span class="text-xs text-amber-700">Cambia esta contraseña en tu primer inicio de sesión</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legacy Password Information Alert (for backward compatibility) -->
            <div
                v-else-if="
                    ($page.props.flash.show_password_info && $page.props.flash.temp_password) ||
                    (sessionData.tenant_creation_success && sessionData.tenant_temp_password && sessionData.tenant_id === tenant.id)
                "
                class="mb-8 rounded-lg border border-amber-200 bg-amber-50 p-6"
            >
                <div class="flex items-start gap-4">
                    <Icon name="key" class="mt-0.5 h-6 w-6 flex-shrink-0 text-amber-600" />
                    <div class="flex-1">
                        <h3 class="mb-2 text-lg font-semibold text-amber-800">Credenciales del Administrador del Tenant</h3>
                        <div class="space-y-3 text-sm">
                            <p class="text-amber-700">El usuario administrador ha sido creado con las siguientes credenciales:</p>
                            <div class="rounded-md border border-amber-200 bg-white p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <p class="font-medium text-gray-700">Email:</p>
                                        <p class="rounded bg-gray-50 p-2 font-mono text-sm">{{ tenant.email || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">Password Temporal:</p>
                                        <div class="flex items-center gap-2">
                                            <p class="flex-1 rounded bg-gray-50 p-2 font-mono text-sm">
                                                {{ $page.props.flash.temp_password || sessionData.tenant_temp_password }}
                                            </p>
                                            <Button @click="copyPassword" variant="outline" size="sm" class="gap-1">
                                                <Icon name="copy" class="h-4 w-4" />
                                                Copiar
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <Icon name="alert-triangle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" />
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
                                    <p class="font-mono text-base">{{ tenant.id }}</p>
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
                            <div v-for="domain in tenant.domains" :key="domain.id" class="flex items-center justify-between rounded-lg border p-4">
                                <div class="flex items-center gap-3">
                                    <Icon name="globe" class="h-4 w-4 text-blue-600" />
                                    <div>
                                        <p class="font-medium">{{ domain.domain }}</p>
                                        <p v-if="domain.is_primary" class="text-xs text-green-600">Dominio principal</p>
                                    </div>
                                </div>
                                <Badge v-if="domain.is_primary" variant="default" class="text-xs"> Principal </Badge>
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
                        <Button v-if="tenant.status === 'active'" @click="impersonateTenant" class="gap-2 bg-green-600 hover:bg-green-700">
                            <Icon name="log-in" class="h-4 w-4" />
                            Ingresar al Tenant
                        </Button>

                        <AlertDialog v-model:open="suspendDialogOpen">
                            <AlertDialogTrigger as-child>
                                <Button
                                    v-if="tenant.status === 'active'"
                                    variant="outline"
                                    class="gap-2 border-orange-200 text-orange-600 hover:bg-orange-50"
                                >
                                    <Icon name="pause" class="h-4 w-4" />
                                    Suspender Tenant
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Suspender Tenant</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        ¿Estás seguro de que quieres suspender el tenant "{{ tenant.name }}"?<br /><br />
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
                                <Button
                                    v-if="tenant.status === 'suspended'"
                                    variant="outline"
                                    class="gap-2 border-green-200 text-green-600 hover:bg-green-50"
                                >
                                    <Icon name="play" class="h-4 w-4" />
                                    Reactivar Tenant
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Reactivar Tenant</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        ¿Estás seguro de que quieres reactivar el tenant "{{ tenant.name }}"?<br /><br />
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
                                        ¿Estás seguro de que quieres eliminar el tenant "{{ tenant.name }}"?<br /><br />
                                        <strong class="text-red-600">Esta acción no se puede deshacer</strong> y se eliminarán todos los datos
                                        asociados.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                    <AlertDialogAction @click="deleteTenant" class="bg-red-600 hover:bg-red-700"> Eliminar Tenant </AlertDialogAction>
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
                    <pre class="overflow-auto rounded-md bg-muted p-4 text-xs">{{ JSON.stringify(tenant.raw_data, null, 2) }}</pre>
                </CardContent>
            </Card>
        </div>

        <!-- User Selection Dialog -->
        <div v-if="showUserDialog" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black" @click="showUserDialog = false">
            <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6" @click.stop>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Seleccionar Usuario para Impersonar</h3>
                    <Button variant="ghost" @click="showUserDialog = false" class="p-1">
                        <Icon name="x" class="h-4 w-4" />
                    </Button>
                </div>

                <div v-if="loadingUsers" class="py-8 text-center">
                    <p>Cargando usuarios...</p>
                </div>

                <div v-else-if="tenantUsers.length === 0" class="py-8 text-center">
                    <p class="text-muted-foreground">No se encontraron usuarios en este tenant</p>
                </div>

                <div v-else class="max-h-64 space-y-2 overflow-y-auto">
                    <div
                        v-for="user in tenantUsers"
                        :key="user.id"
                        class="flex cursor-pointer items-center justify-between rounded-lg border p-3 hover:bg-gray-50"
                        @click="impersonateUser(user.id)"
                    >
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
import Icon from '@/components/Icon.vue';
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
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

interface Domain {
    id: string;
    domain: string;
    is_primary: boolean;
}

interface TenantUser {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface Tenant {
    id: string;
    name: string;
    email: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    domains: Domain[];
    raw_data: any;
}

interface Props {
    tenant: Tenant;
}

const props = defineProps<Props>();

// Access current user data
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => user.value?.roles?.some((role: any) => role.name === 'superadmin'));

// Get session data for tenant creation
const sessionData = computed(() => ({
    tenant_creation_success: page.props.session?.tenant_creation_success,
    tenant_temp_password: page.props.session?.tenant_temp_password,
    tenant_id: page.props.session?.tenant_id,
}));

// Tenant users management
const tenantUsers = ref<TenantUser[]>([]);
const loadingUsers = ref(false);
const showUserDialog = ref(false);

// Dialog states
const deleteDialogOpen = ref(false);
const suspendDialogOpen = ref(false);
const activateDialogOpen = ref(false);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Panel Central', href: '/dashboard' },
    { title: 'Gestión de Tenants', href: '/tenants' },
    { title: props.tenant.name, href: `/tenants/${props.tenant.id}` },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'suspended':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'active':
            return 'Activo';
        case 'suspended':
            return 'Suspendido';
        default:
            return status;
    }
};

const loadTenantUsers = async () => {
    if (props.tenant.status !== 'active') return;

    loadingUsers.value = true;
    try {
        const url = `/tenants/${props.tenant.id}/users`;
        const response = await axios.get(url);
        tenantUsers.value = response.data.users || [];
        showUserDialog.value = true;
    } catch (error) {
        console.error('Error loading tenant users:', error);
        console.error('Error al cargar los usuarios del tenant');
    } finally {
        loadingUsers.value = false;
    }
};

const impersonateUser = async (userId: number) => {
    try {
        const url = `/tenants/${props.tenant.id}/impersonate`;
        const response = await axios.post(url, {
            user_id: userId,
            redirect_url: '/dashboard',
        });

        if (response.data.success) {
            // Open tenant URL with impersonation token
            window.open(response.data.url, '_blank');
            showUserDialog.value = false;
        } else {
            console.error('Error al generar token de impersonación');
        }
    } catch (error: any) {
        console.error('Error impersonating user:', error);
        const errorMessage = error.response?.data?.error || 'Error al impersonar usuario';
        console.error(errorMessage);
    }
};

const impersonateTenant = () => {
    loadTenantUsers();
};

const suspendTenant = () => {
    suspendDialogOpen.value = false;
    router.post(route('tenant-management.suspend', props.tenant.id));
};

const activateTenant = () => {
    activateDialogOpen.value = false;
    router.post(route('tenant-management.activate', props.tenant.id));
};

const deleteTenant = () => {
    deleteDialogOpen.value = false;
    router.delete(route('tenant-management.destroy', props.tenant.id));
};

const copyPassword = () => {
    const password = page.props.flash.temp_password || sessionData.value.tenant_temp_password;
    if (password) {
        navigator.clipboard.writeText(password).then(() => {
            console.log('Password copied to clipboard');
        });
    }
};
</script>
