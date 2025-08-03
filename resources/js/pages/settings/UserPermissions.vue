<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import { Settings, Shield, User, Users } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

interface User {
    id: number;
    name: string;
    email: string;
    roles: Array<{ id: number; name: string }>;
    permissions: Array<{ id: number; name: string }>; // Direct permissions only
    all_permissions: Array<string>; // All permissions (role + direct)
}

interface Role {
    id: number;
    name: string;
    permissions: Array<{ id: number; name: string }>;
}

interface Permission {
    id: number;
    name: string;
}

interface Props {
    users: User[];
    roles: Role[];
    permissions: Record<string, Permission[]>;
    flash?: {
        success?: string;
        error?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const { success: showSuccess, error: showError } = useToast();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Permisos de Usuario',
        href: '/settings/permissions',
    },
];

const selectedUser = ref<User | null>(null);
const selectedRole = ref<Role | null>(null);
const activeTab = ref('users');

// No longer need separate array - use rolePermissionsForm.permissions directly

const userRoleForm = useForm({
    role: '',
});

const userPermissionsForm = useForm({
    permissions: [] as string[],
});

const rolePermissionsForm = useForm({
    permissions: [] as string[],
});

const selectUser = (user: User) => {
    selectedUser.value = user;
    userRoleForm.role = user.roles[0]?.name || '';
    // Only load direct permissions for editing (not role-based permissions)
    userPermissionsForm.permissions = user.permissions.map((p) => p.name);
};

const selectRole = (role: Role) => {
    selectedRole.value = role;
    const permissionNames = role.permissions.map((p) => p.name);

    // Update the form with role permissions
    rolePermissionsForm.permissions = [...permissionNames];
};

const updateUserRole = () => {
    if (!selectedUser.value) return;

    const currentUserId = selectedUser.value.id;

    userRoleForm.patch(`/settings/permissions/users/${selectedUser.value.id}/role`, {
        preserveScroll: true,
        onSuccess: () => {
            // Reload data via Inertia without losing Vue state
            router.reload({
                preserveScroll: true,
                onSuccess: () => {
                    // Re-select the same user after data refresh
                    nextTick(() => {
                        const refreshedUser = props.users.find((u) => u.id === currentUserId);
                        if (refreshedUser) {
                            selectUser(refreshedUser);
                        }
                    });
                },
            });
        },
    });
};

const updateUserPermissions = () => {
    if (!selectedUser.value) return;

    const currentUserId = selectedUser.value.id;

    userPermissionsForm.patch(`/settings/permissions/users/${selectedUser.value.id}/permissions`, {
        preserveScroll: true,
        onSuccess: () => {
            // Reload data via Inertia without losing Vue state
            router.reload({
                preserveScroll: true,
                onSuccess: () => {
                    // Re-select the same user after data refresh
                    nextTick(() => {
                        const refreshedUser = props.users.find((u) => u.id === currentUserId);
                        if (refreshedUser) {
                            selectUser(refreshedUser);
                        }
                    });
                },
            });
        },
    });
};

const updateRolePermissions = () => {
    if (!selectedRole.value) return;

    const currentRoleId = selectedRole.value.id;

    rolePermissionsForm.patch(`/settings/permissions/roles/${selectedRole.value.id}/permissions`, {
        preserveScroll: true,
        onSuccess: () => {
            // Reload data via Inertia without losing Vue state
            router.reload({
                preserveScroll: true,
                onSuccess: () => {
                    // Re-select the same role after data refresh
                    nextTick(() => {
                        const refreshedRole = props.roles.find((r) => r.id === currentRoleId);
                        if (refreshedRole) {
                            selectRole(refreshedRole);
                        }
                    });
                },
            });
        },
    });
};

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName) {
        case 'superadmin':
            return 'destructive';
        case 'admin_conjunto':
            return 'default';
        case 'consejo':
            return 'secondary';
        case 'propietario':
            return 'outline';
        case 'residente':
            return 'outline';
        case 'porteria':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getRoleDisplayName = (roleName: string) => {
    const roleNames: Record<string, string> = {
        superadmin: 'Super Admin',
        admin_conjunto: 'Admin Conjunto',
        consejo: 'Consejo',
        propietario: 'Propietario',
        residente: 'Residente',
        porteria: 'Portería',
    };
    return roleNames[roleName] || roleName;
};

// Handle flash messages
onMounted(() => {
    if (props.flash?.success) {
        showSuccess(props.flash.success, 'Operación exitosa', { duration: 3000 });
    }
    if (props.flash?.error) {
        showError(props.flash.error, 'Error', { duration: 5000 });
    }
});

// Watch for flash messages changes
watch(
    () => page.props.flash,
    (newFlash: any) => {
        if (newFlash?.success) {
            showSuccess(newFlash.success, 'Operación exitosa', { duration: 3000 });
        }
        if (newFlash?.error) {
            showError(newFlash.error, 'Error', { duration: 5000 });
        }
    },
    { deep: true },
);

// Watch for roles changes to update selected role permissions
watch(
    () => props.roles,
    (newRoles) => {
        if (selectedRole.value && newRoles) {
            // Find the updated role data
            const updatedRole = newRoles.find((r) => r.id === selectedRole.value?.id);
            if (updatedRole) {
                // Update the selected role with fresh data
                selectedRole.value = updatedRole;
                const permissionNames = updatedRole.permissions.map((p) => p.name);
                rolePermissionsForm.permissions = [...permissionNames];
            }
        }
    },
    { deep: true },
);

// Watch for users changes to update selected user permissions
watch(
    () => props.users,
    (newUsers) => {
        if (selectedUser.value && newUsers) {
            // Find the updated user data
            const updatedUser = newUsers.find((u) => u.id === selectedUser.value?.id);
            if (updatedUser) {
                // Update the selected user with fresh data
                selectedUser.value = updatedUser;
                userRoleForm.role = updatedUser.roles[0]?.name || '';
                userPermissionsForm.permissions = updatedUser.permissions.map((p) => p.name);
            }
        }
    },
    { deep: true },
);

const getPermissionDisplayName = (permissionName: string) => {
    const permissionNames: Record<string, string> = {
        view_dashboard: 'Ver Dashboard',
        view_conjunto_config: 'Ver Config. Conjunto',
        edit_conjunto_config: 'Editar Config. Conjunto',
        view_apartments: 'Ver Apartamentos',
        create_apartments: 'Crear Apartamentos',
        edit_apartments: 'Editar Apartamentos',
        delete_apartments: 'Eliminar Apartamentos',
        view_residents: 'Ver Residentes',
        create_residents: 'Crear Residentes',
        edit_residents: 'Editar Residentes',
        delete_residents: 'Eliminar Residentes',
        view_payments: 'Ver Pagos',
        create_payments: 'Crear Pagos',
        edit_payments: 'Editar Pagos',
        delete_payments: 'Eliminar Pagos',
        view_reports: 'Ver Reportes',
        manage_invitations: 'Gestionar Invitaciones',
        view_announcements: 'Ver Comunicados',
        create_announcements: 'Crear Comunicados',
        edit_announcements: 'Editar Comunicados',
        delete_announcements: 'Eliminar Comunicados',
        send_notifications: 'Enviar Notificaciones',
        view_access_logs: 'Ver Logs de Acceso',
        manage_visitors: 'Gestionar Visitantes',
        view_security_reports: 'Ver Reportes de Seguridad',
        view_account_statement: 'Ver Estado de Cuenta',
        invite_visitors: 'Invitar Visitantes',
        receive_notifications: 'Recibir Notificaciones',
        send_pqrs: 'Enviar PQRS',
        send_messages_to_admin: 'Enviar Mensajes a Admin',
        review_provider_proposals: 'Revisar Propuestas de Proveedores',
        view_users: 'Ver Usuarios',
        create_users: 'Crear Usuarios',
        edit_users: 'Editar Usuarios',
        delete_users: 'Eliminar Usuarios',
    };
    return permissionNames[permissionName] || permissionName;
};
</script>

<template>
    <Head title="Permisos de Usuario" />

    <AppLayout>
        <SettingsLayout>
            <template #breadcrumb>
                <HeadingSmall :breadcrumb-items="breadcrumbItems" />
            </template>

            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium">Gestión de Permisos</h3>
                    <p class="text-sm text-muted-foreground">Configura los roles y permisos de cada usuario en el sistema.</p>
                </div>

                <Separator />

                <Tabs v-model="activeTab" class="space-y-4">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="users" class="flex items-center gap-2">
                            <User class="h-4 w-4" />
                            Usuarios
                        </TabsTrigger>
                        <TabsTrigger value="roles" class="flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Roles
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="users" class="space-y-4">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- Users List -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <User class="h-5 w-5" />
                                        Lista de Usuarios
                                    </CardTitle>
                                    <CardDescription> Selecciona un usuario para gestionar sus permisos </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <div
                                        v-for="user in users"
                                        :key="user.id"
                                        class="cursor-pointer rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                        :class="{ 'border-primary bg-primary/5': selectedUser?.id === user.id }"
                                        @click="selectUser(user)"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">{{ user.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <Badge
                                                    v-for="role in user.roles"
                                                    :key="role.id"
                                                    :variant="getRoleBadgeVariant(role.name)"
                                                    class="text-xs"
                                                >
                                                    {{ getRoleDisplayName(role.name) }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- User Permissions Management -->
                            <div v-if="selectedUser" class="space-y-4">
                                <!-- Role Assignment -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Shield class="h-5 w-5" />
                                            Rol del Usuario
                                        </CardTitle>
                                        <CardDescription> Asigna un rol a {{ selectedUser.name }} </CardDescription>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="user-role">Rol</Label>
                                            <Select v-model="userRoleForm.role">
                                                <SelectTrigger id="user-role">
                                                    <SelectValue placeholder="Selecciona un rol" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                                        {{ getRoleDisplayName(role.name) }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Button @click="updateUserRole" :disabled="userRoleForm.processing" class="w-full">
                                            {{ userRoleForm.processing ? 'Actualizando...' : 'Actualizar Rol' }}
                                        </Button>
                                    </CardContent>
                                </Card>

                                <!-- Individual Permissions -->
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Settings class="h-5 w-5" />
                                            Permisos Individuales
                                        </CardTitle>
                                        <CardDescription> Permisos específicos para {{ selectedUser.name }} </CardDescription>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div v-for="(perms, category) in permissions" :key="category" class="space-y-2">
                                            <h4 class="text-sm font-medium">{{ category }}</h4>
                                            <div class="ml-4 grid grid-cols-1 gap-2">
                                                <div v-for="permission in perms" :key="permission.id" class="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        :id="`user-perm-${permission.id}`"
                                                        :checked="userPermissionsForm.permissions.includes(permission.name)"
                                                        @change="
                                                            (event) => {
                                                                const checked = (event.target as HTMLInputElement).checked;
                                                                if (checked) {
                                                                    userPermissionsForm.permissions = [
                                                                        ...(userPermissionsForm.permissions || []),
                                                                        permission.name,
                                                                    ];
                                                                } else {
                                                                    userPermissionsForm.permissions = (userPermissionsForm.permissions || []).filter(
                                                                        (p) => p !== permission.name,
                                                                    );
                                                                }
                                                            }
                                                        "
                                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <Label :for="`user-perm-${permission.id}`" class="text-sm font-normal">
                                                        {{ getPermissionDisplayName(permission.name) }}
                                                    </Label>
                                                </div>
                                            </div>
                                        </div>
                                        <Button @click="updateUserPermissions" :disabled="userPermissionsForm.processing" class="w-full">
                                            {{ userPermissionsForm.processing ? 'Actualizando...' : 'Actualizar Permisos' }}
                                        </Button>
                                    </CardContent>
                                </Card>
                            </div>

                            <div v-else class="flex h-64 items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <User class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p>Selecciona un usuario para gestionar sus permisos</p>
                                </div>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="roles" class="space-y-4">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- Roles List -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Users class="h-5 w-5" />
                                        Lista de Roles
                                    </CardTitle>
                                    <CardDescription> Selecciona un rol para gestionar sus permisos </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <div
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="cursor-pointer rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                        :class="{ 'border-primary bg-primary/5': selectedRole?.id === role.id }"
                                        @click="selectRole(role)"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">{{ getRoleDisplayName(role.name) }}</p>
                                                <p class="text-sm text-muted-foreground">{{ role.permissions.length }} permisos asignados</p>
                                            </div>
                                            <Badge :variant="getRoleBadgeVariant(role.name)">
                                                {{ role.name }}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Role Permissions Management -->
                            <div v-if="selectedRole" class="space-y-4">
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="flex items-center gap-2">
                                            <Settings class="h-5 w-5" />
                                            Permisos del Rol
                                        </CardTitle>
                                        <CardDescription>
                                            Gestiona los permisos para el rol {{ getRoleDisplayName(selectedRole.name) }}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div v-for="(perms, category) in permissions" :key="category" class="space-y-2">
                                            <h4 class="text-sm font-medium">{{ category }}</h4>
                                            <div class="ml-4 grid grid-cols-1 gap-2">
                                                <div v-for="permission in perms" :key="permission.id" class="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        :id="`role-perm-${permission.id}`"
                                                        :checked="(rolePermissionsForm.permissions || []).includes(permission.name)"
                                                        @change="
                                                            (event) => {
                                                                const checked = (event.target as HTMLInputElement).checked;
                                                                if (checked) {
                                                                    rolePermissionsForm.permissions = [
                                                                        ...(rolePermissionsForm.permissions || []),
                                                                        permission.name,
                                                                    ];
                                                                } else {
                                                                    rolePermissionsForm.permissions = (rolePermissionsForm.permissions || []).filter(
                                                                        (p) => p !== permission.name,
                                                                    );
                                                                }
                                                            }
                                                        "
                                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <Label :for="`role-perm-${permission.id}`" class="text-sm font-normal">
                                                        {{ getPermissionDisplayName(permission.name) }}
                                                    </Label>
                                                </div>
                                            </div>
                                        </div>
                                        <Button @click="updateRolePermissions" :disabled="rolePermissionsForm.processing" class="w-full">
                                            {{ rolePermissionsForm.processing ? 'Actualizando...' : 'Actualizar Permisos' }}
                                        </Button>
                                    </CardContent>
                                </Card>
                            </div>

                            <div v-else class="flex h-64 items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <Users class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p>Selecciona un rol para gestionar sus permisos</p>
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
