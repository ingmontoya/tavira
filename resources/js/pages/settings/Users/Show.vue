<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Mail, Phone, User, UserCog, Users, Calendar } from 'lucide-vue-next';

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    position?: string;
    department?: string;
    is_active: boolean;
    roles: Array<{
        id: number;
        name: string;
    }>;
    created_at: string;
    updated_at: string;
    email_verified_at?: string;
}

const props = defineProps<{
    user: User;
}>();

const breadcrumbs = [
    {
        title: 'Configuración',
        href: '/settings',
    },
    {
        title: 'Usuarios',
        href: '/settings/users',
    },
    {
        title: props.user.name,
        href: `/settings/users/${props.user.id}`,
    },
];

const roleLabels: Record<string, string> = {
    superadmin: 'Super Administrador',
    admin_conjunto: 'Administrador del Conjunto',
    consejo: 'Miembro del Consejo',
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

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName) {
        case 'superadmin':
            return 'destructive';
        case 'admin_conjunto':
            return 'default';
        case 'consejo':
            return 'secondary';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head :title="`Usuario - ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="space-y-6">
                <!-- Header Section -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                            <User class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <HeadingSmall 
                                :title="user.name" 
                                :description="user.position ? `${user.position}${user.department ? ` - ${user.department}` : ''}` : 'Usuario del sistema'" 
                            />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button @click="router.visit(`/settings/users/${user.id}/edit`)">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar Usuario
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Información Personal -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Información Personal
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <Mail class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-muted-foreground">Email:</span>
                                </div>
                                <p class="font-medium">{{ user.email }}</p>
                                <div v-if="user.email_verified_at" class="flex items-center gap-1">
                                    <Badge variant="secondary" class="text-xs">Verificado</Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formatDate(user.email_verified_at) }}
                                    </span>
                                </div>
                                <div v-else>
                                    <Badge variant="destructive" class="text-xs">No Verificado</Badge>
                                </div>
                            </div>

                            <div v-if="user.phone" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <Phone class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-muted-foreground">Teléfono:</span>
                                </div>
                                <p class="font-medium">{{ user.phone }}</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-muted-foreground">Estado:</span>
                                </div>
                                <Badge :variant="user.is_active ? 'secondary' : 'destructive'">
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Roles y Permisos -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <UserCog class="h-5 w-5" />
                                Roles y Permisos
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">Rol asignado:</p>
                                <div v-if="user.roles.length > 0">
                                    <Badge 
                                        v-for="role in user.roles" 
                                        :key="role.id"
                                        :variant="getRoleBadgeVariant(role.name)"
                                        class="mb-1 mr-1"
                                    >
                                        {{ roleLabels[role.name] || role.name }}
                                    </Badge>
                                </div>
                                <div v-else>
                                    <Badge variant="outline">Sin rol asignado</Badge>
                                </div>
                            </div>

                            <div v-if="user.position" class="space-y-2">
                                <p class="text-sm text-muted-foreground">Cargo:</p>
                                <p class="font-medium">{{ user.position }}</p>
                            </div>

                            <div v-if="user.department" class="space-y-2">
                                <p class="text-sm text-muted-foreground">Departamento:</p>
                                <p class="font-medium">{{ user.department }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información del Sistema -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Información del Sistema
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">Fecha de registro:</p>
                                <p class="font-medium">{{ formatDate(user.created_at) }}</p>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">Última actualización:</p>
                                <p class="font-medium">{{ formatDate(user.updated_at) }}</p>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">ID del usuario:</p>
                                <p class="font-mono text-sm">{{ user.id }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Estadísticas de Actividad -->
                <Card>
                    <CardHeader>
                        <CardTitle>Actividad Reciente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <Users class="mb-4 h-16 w-16 text-muted-foreground" />
                            <h3 class="mb-2 text-lg font-semibold">Estadísticas de Actividad</h3>
                            <p class="mb-6 max-w-md text-muted-foreground">
                                Las estadísticas de actividad del usuario estarán disponibles próximamente.
                                Aquí podrás ver el historial de accesos, últimas acciones realizadas y más.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>