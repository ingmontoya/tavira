<script setup lang="ts">
import MaintenanceRequestDocuments from '@/components/MaintenanceRequestDocuments.vue';
import MermaidDiagram from '@/components/MermaidDiagram.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Calendar, Check, CheckCircle, Clock, DollarSign, Edit, FileText, MapPin, Play, User, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

export interface MaintenanceRequest {
    id: number;
    title: string;
    description: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    priority_badge_color: string;
    status: string;
    status_badge_color: string;
    location: string | null;
    estimated_cost: number | null;
    actual_cost: number | null;
    estimated_completion_date: string | null;
    actual_completion_date: string | null;
    requires_council_approval: boolean;
    council_approved_at: string | null;
    notes: any[] | null;
    attachments: any[] | null;
    created_at: string;
    updated_at: string;
    maintenance_category: {
        id: number;
        name: string;
        color: string;
    };
    apartment: {
        id: number;
        number: string;
        tower: string;
        apartment_type: {
            name: string;
        };
    } | null;
    requested_by: {
        id: number;
        name: string;
    };
    assigned_staff: {
        id: number;
        name: string;
    } | null;
    approved_by: {
        id: number;
        name: string;
    } | null;
    work_orders: any[];
}

export interface MaintenanceStaff {
    id: number;
    name: string;
    specialties: string[];
    is_internal: boolean;
    is_active: boolean;
}

interface Props {
    maintenanceRequest: MaintenanceRequest;
    staff: MaintenanceStaff[];
    permissions: {
        can_approve: boolean;
        can_assign: boolean;
        can_complete: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
    nextState: {
        can_transition: boolean;
        next_status: string | null;
        next_status_label: string | null;
    };
}

const props = defineProps<Props>();

const assignForm = useForm({
    assigned_staff_id: '',
});

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

const canApprove = computed(() => {
    return props.permissions.can_approve && props.maintenanceRequest.status === 'pending_approval';
});

const canAssign = computed(() => {
    return props.permissions.can_assign && props.maintenanceRequest.status === 'approved';
});

const canStartWork = computed(() => {
    return props.permissions.can_complete && props.maintenanceRequest.status === 'assigned';
});

const canComplete = computed(() => {
    return props.permissions.can_complete && props.maintenanceRequest.status === 'in_progress';
});

const mermaidDiagram = computed(() => {
    // Use original complex diagram
    const currentStatus = props.maintenanceRequest.status;

    // Define main flow statuses
    const mainFlow = [
        { key: 'created', label: 'Creada' },
        { key: 'evaluation', label: 'En Evaluación' },
        { key: 'budgeted', label: 'Presupuestada' },
        { key: 'pending_approval', label: 'Pendiente<br/>Aprobación' },
        { key: 'approved', label: 'Aprobada' },
        { key: 'assigned', label: 'Asignada' },
        { key: 'in_progress', label: 'En Progreso' },
        { key: 'completed', label: 'Completada' },
        { key: 'closed', label: 'Cerrada' },
    ];

    // Alternative states
    const alternativeStates = [
        { key: 'rejected', label: 'Rechazada' },
        { key: 'suspended', label: 'Suspendida' },
    ];

    let diagram = 'flowchart LR\n';

    // Add main flow
    mainFlow.forEach((status, index) => {
        const isCurrentStatus = status.key === currentStatus;
        const currentIndex = mainFlow.findIndex((s) => s.key === currentStatus);
        const isPastStatus = currentIndex > index;

        let style = '';
        if (isCurrentStatus) {
            style = ':::current';
        } else if (isPastStatus && currentIndex !== -1) {
            style = ':::completed';
        } else {
            style = ':::pending';
        }

        diagram += `    ${status.key}["${status.label}"]${style}\n`;

        if (index < mainFlow.length - 1) {
            diagram += `    ${status.key} --> ${mainFlow[index + 1].key}\n`;
        }
    });

    // Add alternative states
    alternativeStates.forEach((status) => {
        const isCurrentStatus = status.key === currentStatus;
        const style = isCurrentStatus ? ':::current' : ':::alternative';
        diagram += `    ${status.key}["${status.label}"]${style}\n`;

        // Connect alternatives from key decision points
        if (status.key === 'rejected') {
            diagram += `    pending_approval -.-> rejected\n`;
        }
        if (status.key === 'suspended') {
            diagram += `    in_progress -.-> suspended\n`;
        }
    });

    // Add styling classes
    diagram += `\n    classDef current fill:#10b981,stroke:#059669,stroke-width:3px,color:#fff\n`;
    diagram += `    classDef completed fill:#d1fae5,stroke:#10b981,stroke-width:2px,color:#065f46\n`;
    diagram += `    classDef pending fill:#f3f4f6,stroke:#9ca3af,stroke-width:1px,color:#6b7280\n`;
    diagram += `    classDef alternative fill:#fef2f2,stroke:#ef4444,stroke-width:2px,color:#dc2626\n`;

    return diagram;
});

const goBack = () => {
    router.visit(route('maintenance-requests.index'));
};

const editRequest = () => {
    router.visit(route('maintenance-requests.edit', props.maintenanceRequest.id));
};

const approveRequest = () => {
    router.patch(route('maintenance-requests.approve', props.maintenanceRequest.id));
};

const assignStaff = () => {
    assignForm.patch(route('maintenance-requests.assign', props.maintenanceRequest.id));
};

const startWork = () => {
    router.patch(route('maintenance-requests.start-work', props.maintenanceRequest.id));
};

const completeRequest = () => {
    router.patch(route('maintenance-requests.complete', props.maintenanceRequest.id));
};

const transitionToNextState = () => {
    router.patch(route('maintenance-requests.next-state', props.maintenanceRequest.id));
};

const formatCurrency = (amount: number | null) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string | null) => {
    if (!date) return 'No definida';
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
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
        title: 'Solicitudes',
        href: '/maintenance-requests',
    },
    {
        title: props.maintenanceRequest.title,
        href: `/maintenance-requests/${props.maintenanceRequest.id}`,
    },
];
</script>

<template>
    <Head :title="`Solicitud: ${maintenanceRequest.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Wrench class="h-6 w-6 text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">{{ maintenanceRequest.title }}</h1>
                        <p class="text-sm text-gray-600">Solicitud #{{ maintenanceRequest.id }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" @click="editRequest">
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Estado y Acciones -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center space-x-2">
                                <Clock class="h-5 w-5" />
                                <span>Estado y Acciones</span>
                            </CardTitle>
                            <div class="flex items-center space-x-2">
                                <Badge :style="{ backgroundColor: maintenanceRequest.maintenance_category.color, color: 'white' }">
                                    {{ maintenanceRequest.maintenance_category.name }}
                                </Badge>
                                <Badge :variant="maintenanceRequest.priority_badge_color as any">
                                    {{ priorityLabels[maintenanceRequest.priority] }}
                                </Badge>
                                <Badge :variant="maintenanceRequest.status_badge_color as any">
                                    {{ statusLabels[maintenanceRequest.status as keyof typeof statusLabels] }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Aprobar -->
                            <div v-if="canApprove">
                                <Button @click="approveRequest" class="w-full">
                                    <CheckCircle class="mr-2 h-4 w-4" />
                                    Aprobar Solicitud
                                </Button>
                            </div>

                            <!-- Asignar Personal -->
                            <div v-if="canAssign" class="space-y-2">
                                <Select v-model="assignForm.assigned_staff_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar personal" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="person in staff" :key="person.id" :value="person.id.toString()">
                                            <div class="space-y-1">
                                                <div class="font-medium">{{ person.name }}</div>
                                                <div class="text-sm text-gray-500">
                                                    {{ person.specialties.join(', ') }}
                                                </div>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button @click="assignStaff" :disabled="!assignForm.assigned_staff_id || assignForm.processing" class="w-full">
                                    <User class="mr-2 h-4 w-4" />
                                    Asignar Personal
                                </Button>
                            </div>

                            <!-- Iniciar Trabajo -->
                            <div v-if="canStartWork">
                                <Button @click="startWork" class="w-full">
                                    <Play class="mr-2 h-4 w-4" />
                                    Iniciar Trabajo
                                </Button>
                            </div>

                            <!-- Completar -->
                            <div v-if="canComplete">
                                <Button @click="completeRequest" class="w-full">
                                    <Check class="mr-2 h-4 w-4" />
                                    Marcar Completada
                                </Button>
                            </div>

                            <!-- Siguiente Estado -->
                            <div v-if="nextState.can_transition && permissions.can_edit">
                                <Button @click="transitionToNextState" variant="outline" class="w-full">
                                    <ArrowRight class="mr-2 h-4 w-4" />
                                    Avanzar a: {{ nextState.next_status_label }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Diagrama de Flujo de Estados -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Wrench class="h-5 w-5" />
                            <span>Flujo de Estados</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="w-full overflow-x-auto">
                            <MermaidDiagram :definition="mermaidDiagram" />
                        </div>
                    </CardContent>
                </Card>

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
                                <p class="mt-1 text-sm text-gray-900">{{ maintenanceRequest.description }}</p>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Solicitado por</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ maintenanceRequest.requested_by.name }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Asignado a</h4>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ maintenanceRequest.assigned_staff?.name || 'Sin asignar' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="maintenanceRequest.apartment" class="flex items-center space-x-2">
                                <MapPin class="h-4 w-4 text-gray-500" />
                                <span class="text-sm">
                                    Apartamento {{ maintenanceRequest.apartment.number }} - Torre {{ maintenanceRequest.apartment.tower }} ({{
                                        maintenanceRequest.apartment.apartment_type.name
                                    }})
                                </span>
                            </div>

                            <div v-if="maintenanceRequest.location" class="flex items-center space-x-2">
                                <MapPin class="h-4 w-4 text-gray-500" />
                                <span class="text-sm">{{ maintenanceRequest.location }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información Financiera y Fechas -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <DollarSign class="h-5 w-5" />
                                <span>Costos y Fechas</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Costo Estimado</h4>
                                    <p class="mt-1 text-sm font-medium text-gray-900">
                                        {{ formatCurrency(maintenanceRequest.estimated_cost) }}
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Costo Real</h4>
                                    <p class="mt-1 text-sm font-medium text-gray-900">
                                        {{ formatCurrency(maintenanceRequest.actual_cost) }}
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-1 gap-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <Calendar class="h-4 w-4 text-gray-500" />
                                        <span class="text-sm font-medium text-gray-700">Fecha Est. Completación</span>
                                    </div>
                                    <span class="text-sm text-gray-900">
                                        {{ formatDate(maintenanceRequest.estimated_completion_date) }}
                                    </span>
                                </div>

                                <div v-if="maintenanceRequest.actual_completion_date" class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <Calendar class="h-4 w-4 text-gray-500" />
                                        <span class="text-sm font-medium text-gray-700">Fecha Completación</span>
                                    </div>
                                    <span class="text-sm text-gray-900">
                                        {{ formatDate(maintenanceRequest.actual_completion_date) }}
                                    </span>
                                </div>
                            </div>

                            <Separator />

                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Requiere Aprobación del Concejo</span>
                                    <Badge :variant="maintenanceRequest.requires_council_approval ? 'default' : 'secondary'">
                                        {{ maintenanceRequest.requires_council_approval ? 'Sí' : 'No' }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="maintenanceRequest.requires_council_approval && maintenanceRequest.council_approved_at"
                                    class="mt-2 text-sm text-gray-600"
                                >
                                    Aprobado el {{ formatDate(maintenanceRequest.council_approved_at) }}
                                    <span v-if="maintenanceRequest.approved_by"> por {{ maintenanceRequest.approved_by.name }} </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Documentación -->
                <MaintenanceRequestDocuments :maintenanceRequestId="maintenanceRequest.id" :canEdit="permissions.can_edit" />

                <!-- Órdenes de Trabajo -->
                <Card v-if="maintenanceRequest.work_orders && maintenanceRequest.work_orders.length > 0">
                    <CardHeader>
                        <CardTitle>Órdenes de Trabajo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-gray-600">
                            Esta solicitud tiene {{ maintenanceRequest.work_orders.length }} orden(es) de trabajo asociada(s).
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
