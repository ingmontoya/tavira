<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, FileText, Settings, Wrench } from 'lucide-vue-next';

export interface MaintenanceRequest {
    id: number;
    title: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    priority_badge_color: string;
    status: string;
    status_badge_color: string;
    created_at: string;
    requested_by: {
        id: number;
        name: string;
    };
}

export interface MaintenanceCategory {
    id: number;
    name: string;
    description: string | null;
    color: string;
    priority_level: number;
    requires_approval: boolean;
    estimated_hours: number | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    maintenance_requests: MaintenanceRequest[];
}

interface Props {
    category: MaintenanceCategory;
}

const props = defineProps<Props>();

const priorityLabels = {
    1: 'Crítica',
    2: 'Alta', 
    3: 'Media',
    4: 'Baja',
};

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

const priorityRequestLabels = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    critical: 'Crítica',
};

const goBack = () => {
    router.visit(route('maintenance-categories.index'));
};

const editCategory = () => {
    router.visit(route('maintenance-categories.edit', props.category.id));
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
        title: 'Categorías',
        href: '/maintenance-categories',
    },
    {
        title: props.category.name,
        href: `/maintenance-categories/${props.category.id}`,
    },
];
</script>

<template>
    <Head :title="`Categoría: ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: category.color }"></div>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">{{ category.name }}</h1>
                        <p class="text-sm text-gray-600">Categoría #{{ category.id }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" @click="editCategory">
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
                <!-- Información Principal -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <FileText class="h-5 w-5" />
                            <span>Información Principal</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Descripción</h4>
                            <p class="mt-1 text-sm text-gray-900">{{ category.description || 'Sin descripción' }}</p>
                        </div>

                        <Separator />

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Nivel de Prioridad</h4>
                                <Badge class="mt-1" :variant="category.priority_level <= 2 ? 'destructive' : category.priority_level === 3 ? 'default' : 'secondary'">
                                    {{ priorityLabels[category.priority_level as keyof typeof priorityLabels] || 'Desconocida' }}
                                </Badge>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Estado</h4>
                                <Badge class="mt-1" :variant="category.is_active ? 'default' : 'secondary'">
                                    {{ category.is_active ? 'Activa' : 'Inactiva' }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Requiere Aprobación</h4>
                                <Badge class="mt-1" :variant="category.requires_approval ? 'default' : 'secondary'">
                                    {{ category.requires_approval ? 'Sí' : 'No' }}
                                </Badge>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Horas Estimadas</h4>
                                <p class="mt-1 text-sm text-gray-900">{{ category.estimated_hours ? `${category.estimated_hours}h` : 'No definidas' }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div>
                                <span class="font-medium">Creada:</span> {{ formatDate(category.created_at) }}
                            </div>
                            <div>
                                <span class="font-medium">Actualizada:</span> {{ formatDate(category.updated_at) }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Estadísticas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Settings class="h-5 w-5" />
                            <span>Estadísticas</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ category.maintenance_requests.length }}</div>
                            <p class="text-sm text-gray-600">Solicitudes de Mantenimiento</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Solicitudes de Mantenimiento -->
            <Card v-if="category.maintenance_requests && category.maintenance_requests.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Wrench class="h-5 w-5" />
                        <span>Solicitudes de Mantenimiento ({{ category.maintenance_requests.length }})</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Título</TableHead>
                                    <TableHead>Prioridad</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Solicitado por</TableHead>
                                    <TableHead>Fecha</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="request in category.maintenance_requests" 
                                    :key="request.id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="router.visit(route('maintenance-requests.show', request.id))"
                                >
                                    <TableCell class="font-medium">{{ request.title }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="request.priority_badge_color as any">
                                            {{ priorityRequestLabels[request.priority] }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="request.status_badge_color as any">
                                            {{ statusLabels[request.status as keyof typeof statusLabels] || request.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ request.requested_by.name }}</TableCell>
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
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes</h3>
                        <p class="mt-1 text-sm text-gray-500">Esta categoría aún no tiene solicitudes de mantenimiento asociadas.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>