<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, Edit, Mail, Phone, User, UserCog, Wrench } from 'lucide-vue-next';

export interface MaintenanceRequest {
    id: number;
    title: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    priority_badge_color: string;
    status: string;
    status_badge_color: string;
    created_at: string;
    maintenance_category: {
        id: number;
        name: string;
        color: string;
    };
}

export interface MaintenanceStaff {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    specialties: string[];
    hourly_rate: number | null;
    is_internal: boolean;
    is_active: boolean;
    availability_schedule: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    maintenance_requests: MaintenanceRequest[];
}

interface Props {
    staff: MaintenanceStaff;
}

const props = defineProps<Props>();

const statusLabels = {
    created: 'Creada',
    evaluation: 'En Evaluación',
    budgeted: 'Presupuestada',
    pending_approval: 'Pendiente Aprobación',
    approved: 'Aprobada',
    assigned: 'Asignada',
    in_progress: 'En Progreso',
    completed: 'Completada',
    closed: 'Cerrada',
    rejected: 'Rechazada',
    suspended: 'Suspendida',
};

const priorityLabels = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    critical: 'Crítica',
};

const goBack = () => {
    router.visit(route('maintenance-staff.index'));
};

const editStaff = () => {
    router.visit(route('maintenance-staff.edit', props.staff.id));
};

const formatCurrency = (amount: number | null) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getAvailabilityText = (schedule: Record<string, any> | null) => {
    if (!schedule) return 'No definida';
    const activeDays = Object.keys(schedule).length;
    return `${activeDays} días/semana`;
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Administración',
        href: '#',
    },
    {
        title: 'Mantenimiento',
        href: '#',
    },
    {
        title: 'Personal',
        href: '/maintenance-staff',
    },
    {
        title: props.staff.name,
        href: `/maintenance-staff/${props.staff.id}`,
    },
];
</script>

<template>
    <Head :title="`Personal: ${staff.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <UserCog class="h-8 w-8 text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">{{ staff.name }}</h1>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm text-gray-600">Personal #{{ staff.id }}</p>
                            <Badge :variant="staff.is_internal ? 'default' : 'secondary'" class="text-xs">
                                {{ staff.is_internal ? 'Interno' : 'Externo' }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" @click="editStaff">
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Información Personal -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <User class="h-5 w-5" />
                            <span>Información Personal</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div v-if="staff.phone" class="flex items-center space-x-2">
                                <Phone class="h-4 w-4 text-gray-500" />
                                <span class="text-sm">{{ staff.phone }}</span>
                            </div>
                            <div v-if="staff.email" class="flex items-center space-x-2">
                                <Mail class="h-4 w-4 text-gray-500" />
                                <span class="text-sm">{{ staff.email }}</span>
                            </div>
                            <div v-if="!staff.phone && !staff.email" class="text-sm text-gray-400">Sin información de contacto</div>
                        </div>

                        <Separator />

                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Estado</h4>
                            <Badge class="mt-1" :variant="staff.is_active ? 'default' : 'secondary'">
                                {{ staff.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Tipo de Personal</h4>
                            <Badge class="mt-1" :variant="staff.is_internal ? 'default' : 'secondary'">
                                {{ staff.is_internal ? 'Interno' : 'Externo' }}
                            </Badge>
                        </div>

                        <Separator />

                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div><span class="font-medium">Registrado:</span> {{ formatDate(staff.created_at) }}</div>
                            <div><span class="font-medium">Actualizado:</span> {{ formatDate(staff.updated_at) }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información Profesional -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Wrench class="h-5 w-5" />
                            <span>Información Profesional</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Especialidades</h4>
                            <div v-if="staff.specialties && staff.specialties.length > 0" class="mt-2 flex flex-wrap gap-2">
                                <Badge v-for="specialty in staff.specialties" :key="specialty" variant="outline" class="text-xs">
                                    {{ specialty }}
                                </Badge>
                            </div>
                            <p v-else class="mt-1 text-sm text-gray-400">Sin especialidades definidas</p>
                        </div>

                        <Separator />

                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Tarifa por Hora</h4>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(staff.hourly_rate) }}</p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Calendar class="h-4 w-4 text-gray-500" />
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Disponibilidad</h4>
                                <p class="text-sm text-gray-600">{{ getAvailabilityText(staff.availability_schedule) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Estadísticas -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Clock class="h-5 w-5" />
                        <span>Estadísticas</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ staff.maintenance_requests.length }}</div>
                        <p class="text-sm text-gray-600">Solicitudes Asignadas</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Solicitudes Asignadas -->
            <Card v-if="staff.maintenance_requests && staff.maintenance_requests.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Wrench class="h-5 w-5" />
                        <span>Solicitudes Asignadas ({{ staff.maintenance_requests.length }})</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Título</TableHead>
                                    <TableHead>Categoría</TableHead>
                                    <TableHead>Prioridad</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="request in staff.maintenance_requests"
                                    :key="request.id"
                                    class="cursor-pointer hover:bg-gray-50"
                                    @click="router.visit(route('maintenance-requests.show', request.id))"
                                >
                                    <TableCell class="font-medium">{{ request.title }}</TableCell>
                                    <TableCell>
                                        <Badge :style="{ backgroundColor: request.maintenance_category.color, color: 'white' }" class="text-xs">
                                            {{ request.maintenance_category.name }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="request.priority_badge_color as any">
                                            {{ priorityLabels[request.priority] }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="request.status_badge_color as any">
                                            {{ statusLabels[request.status as keyof typeof statusLabels] || request.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ formatDate(request.created_at) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Estado cuando no hay solicitudes -->
            <Card v-else>
                <CardContent class="py-8">
                    <div class="text-center text-gray-500">
                        <Wrench class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes asignadas</h3>
                        <p class="mt-1 text-sm text-gray-500">Este miembro del personal aún no tiene solicitudes de mantenimiento asignadas.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
