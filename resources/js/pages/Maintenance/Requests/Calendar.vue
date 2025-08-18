<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useNavigation } from '@/composables/useNavigation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, CalendarDays, Filter, List, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

export interface CalendarEvent {
    id: number;
    title: string;
    start: string;
    end?: string;
    status: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    category: string;
    categoryColor: string;
    url: string;
    backgroundColor: string;
    borderColor: string;
}

export interface MaintenanceRequest {
    id: number;
    title: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    priority_badge_color: string;
    status: string;
    status_badge_color: string;
    estimated_completion_date: string;
    actual_completion_date?: string;
    maintenance_category: {
        name: string;
        color: string;
    };
    requested_by: {
        name: string;
    };
    assigned_staff?: {
        name: string;
    };
}

interface Props {
    events: CalendarEvent[];
    requests: MaintenanceRequest[];
    filters: {
        status?: string;
        start?: string;
        end?: string;
    };
}

const props = defineProps<Props>();

// State
const selectedStatus = ref(props.filters.status || 'all');
const selectedMonth = ref(new Date().toISOString().slice(0, 7)); // YYYY-MM format

const { hasPermission } = useNavigation();

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
        title: 'Cronograma',
        href: '/maintenance-requests-calendar',
    },
];

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

// Computed
const filteredRequests = computed(() => {
    let filtered = props.requests;

    if (selectedStatus.value && selectedStatus.value !== 'all') {
        filtered = filtered.filter((request) => request.status === selectedStatus.value);
    }

    return filtered;
});

const currentMonthEvents = computed(() => {
    const [year, month] = selectedMonth.value.split('-').map(Number);
    const monthStart = `${year}-${month.toString().padStart(2, '0')}-01`;
    const monthEnd = new Date(year, month, 0).toISOString().split('T')[0]; // Last day of month

    return props.events.filter((event) => {
        // Compare as strings to avoid timezone issues
        return event.start >= monthStart && event.start <= monthEnd;
    });
});

const eventsByDate = computed(() => {
    const grouped: Record<string, CalendarEvent[]> = {};
    currentMonthEvents.value.forEach((event) => {
        const date = event.start;
        if (!grouped[date]) {
            grouped[date] = [];
        }
        grouped[date].push(event);
    });
    return grouped;
});

// Methods
const applyFilters = () => {
    const [year, month] = selectedMonth.value.split('-').map(Number);
    const startDate = new Date(year, month - 1, 1).toISOString().split('T')[0];
    const endDate = new Date(year, month, 0).toISOString().split('T')[0]; // Last day of the month

    router.get(
        '/maintenance-requests-calendar',
        {
            status: selectedStatus.value === 'all' ? '' : selectedStatus.value,
            start: startDate,
            end: endDate,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    selectedStatus.value = 'all';
    selectedMonth.value = new Date().toISOString().slice(0, 7);
    applyFilters();
};

const navigateToRequest = (requestId: number) => {
    router.visit(`/maintenance-requests/${requestId}`);
};

const goToList = () => {
    router.visit('/maintenance-requests');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getDaysInMonth = () => {
    const [year, month] = selectedMonth.value.split('-').map(Number);
    const firstDay = new Date(year, month - 1, 1); // month - 1 because getMonth() is 0-based
    const lastDay = new Date(year, month, 0); // This gets last day of previous month when month is 1-based
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];

    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push(null);
    }

    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(day);
    }

    return days;
};

const getDateString = (day: number) => {
    const [year, month] = selectedMonth.value.split('-').map(Number);
    return new Date(year, month - 1, day).toISOString().split('T')[0]; // month - 1 because Date constructor expects 0-based month
};

const hasActiveFilters = computed(() => {
    return selectedStatus.value !== 'all';
});

// Watch for month changes to apply filters
watch(selectedMonth, () => {
    applyFilters();
});
</script>

<template>
    <Head title="Cronograma de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Calendar class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Cronograma de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Button variant="outline" @click="goToList">
                        <List class="mr-2 h-4 w-4" />
                        Ver Lista
                    </Button>
                    <Button v-if="hasPermission('create_maintenance_requests')" @click="router.visit('/maintenance-requests/create')">
                        <CalendarDays class="mr-2 h-4 w-4" />
                        Nueva Solicitud
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <Card class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Filtros</h3>
                        <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="month">Mes</Label>
                            <Input id="month" v-model="selectedMonth" type="month" class="w-full" />
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Estado</Label>
                            <Select v-model="selectedStatus">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="created">Creada</SelectItem>
                                    <SelectItem value="evaluation">En Evaluación</SelectItem>
                                    <SelectItem value="budgeted">Presupuestada</SelectItem>
                                    <SelectItem value="pending_approval">Pendiente Aprobación</SelectItem>
                                    <SelectItem value="approved">Aprobada</SelectItem>
                                    <SelectItem value="assigned">Asignada</SelectItem>
                                    <SelectItem value="in_progress">En Progreso</SelectItem>
                                    <SelectItem value="completed">Completada</SelectItem>
                                    <SelectItem value="closed">Cerrada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end">
                            <Button @click="applyFilters" class="w-full">
                                <Filter class="mr-2 h-4 w-4" />
                                Aplicar Filtros
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Calendar View -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>{{ new Date(selectedMonth + '-02').toLocaleDateString('es-CO', { month: 'long', year: 'numeric' }) }}</span>
                        <Badge variant="secondary">{{ currentMonthEvents.length }} solicitudes programadas</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Calendar Grid -->
                    <div class="mb-4 grid grid-cols-7 gap-2">
                        <!-- Day headers -->
                        <div
                            v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']"
                            :key="day"
                            class="border-b p-2 text-center text-sm font-medium text-gray-500"
                        >
                            {{ day }}
                        </div>

                        <!-- Calendar days -->
                        <div v-for="day in getDaysInMonth()" :key="`day-${day}`" class="min-h-[100px] border border-gray-200 bg-white p-2">
                            <template v-if="day">
                                <div class="mb-1 text-sm font-medium">{{ day }}</div>
                                <div v-if="eventsByDate[getDateString(day)]" class="space-y-1">
                                    <div
                                        v-for="event in eventsByDate[getDateString(day)]"
                                        :key="event.id"
                                        class="cursor-pointer rounded p-1 text-xs transition-opacity hover:opacity-80"
                                        :style="{ backgroundColor: event.backgroundColor, color: 'white' }"
                                        @click="navigateToRequest(event.id)"
                                    >
                                        {{ event.title.length > 15 ? event.title.substring(0, 15) + '...' : event.title }}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Upcoming Requests List -->
            <Card>
                <CardHeader>
                    <CardTitle>Próximas Solicitudes ({{ filteredRequests.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="filteredRequests.length > 0" class="space-y-3">
                        <div
                            v-for="request in filteredRequests.slice(0, 10)"
                            :key="request.id"
                            class="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100"
                            @click="navigateToRequest(request.id)"
                        >
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-medium">{{ request.title }}</h4>
                                    <Badge :style="{ backgroundColor: request.maintenance_category.color, color: 'white' }" class="text-xs">
                                        {{ request.maintenance_category.name }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ formatDate(request.estimated_completion_date) }}
                                </p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <Badge :variant="request.priority_badge_color as any" class="text-xs">
                                        {{ priorityLabels[request.priority] }}
                                    </Badge>
                                    <Badge :variant="request.status_badge_color as any" class="text-xs">
                                        {{ statusLabels[request.status as keyof typeof statusLabels] }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">{{ request.requested_by.name }}</p>
                                <p v-if="request.assigned_staff" class="text-xs text-gray-500">Asignado: {{ request.assigned_staff.name }}</p>
                            </div>
                        </div>

                        <div v-if="filteredRequests.length > 10" class="text-center">
                            <Button variant="outline" @click="goToList"> Ver todas las solicitudes ({{ filteredRequests.length }}) </Button>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-gray-500">
                        <Calendar class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes programadas</h3>
                        <p class="mt-1 text-sm text-gray-500">No se encontraron solicitudes con fechas estimadas para el período seleccionado.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
