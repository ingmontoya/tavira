<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import SetupWizard from '@/components/maintenance/SetupWizard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useNavigation } from '@/composables/useNavigation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { CalendarDays, Edit, Eye, Plus, Search, Trash2, Wrench, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';

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
    created_at: string;
    maintenance_category: {
        id: number;
        name: string;
        color: string;
    };
    apartment: {
        id: number;
        number: string;
        tower: string;
    } | null;
    requested_by: {
        id: number;
        name: string;
    };
    assigned_staff: {
        id: number;
        name: string;
    } | null;
}

export interface MaintenanceCategory {
    id: number;
    name: string;
    color: string;
}

interface Props {
    maintenanceRequests: {
        data: MaintenanceRequest[];
        meta: any;
        links: any;
    };
    metrics?: {
        total: number;
        active: number;
        completed_this_month: number;
        critical_priority: number;
        pending_approval: number;
        in_progress: number;
        recurring_active: number;
        recurring_paused: number;
        upcoming_recurring: number;
        total_cost_this_month: number;
    };
    filters: {
        status?: string;
        priority?: string;
        category?: string;
        search?: string;
    };
    categories: MaintenanceCategory[];
    hasCategoriesConfigured: boolean;
    hasStaffConfigured: boolean;
    needsSetup: boolean;
}

const props = defineProps<Props>();

const data = ref(props.maintenanceRequests.data);

// Default metrics if not provided
const metrics = computed(() => props.metrics || {
    total: 0,
    active: 0,
    completed_this_month: 0,
    critical_priority: 0,
    pending_approval: 0,
    in_progress: 0,
    recurring_active: 0,
    recurring_paused: 0,
    upcoming_recurring: 0,
    total_cost_this_month: 0,
});
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

// Search and filter states
const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedPriority = ref(props.filters.priority || 'all');
const selectedCategory = ref(props.filters.category || 'all');

const columnHelper = createColumnHelper<MaintenanceRequest>();

const columns = [
    columnHelper.accessor('title', {
        header: 'Título',
        cell: ({ row }) => {
            return h('div', { class: 'font-medium' }, row.getValue('title'));
        },
    }),
    columnHelper.accessor('maintenance_category', {
        header: 'Categoría',
        cell: ({ row }) => {
            const category = row.getValue('maintenance_category') as MaintenanceRequest['maintenance_category'];
            return h(
                Badge,
                {
                    style: { backgroundColor: category.color, color: 'white' },
                    class: 'text-xs',
                },
                () => category.name,
            );
        },
    }),
    columnHelper.accessor('priority', {
        header: 'Prioridad',
        cell: ({ row }) => {
            const priority = row.getValue('priority') as string;
            const priorityLabels = {
                low: 'Baja',
                medium: 'Media',
                high: 'Alta',
                critical: 'Crítica',
            };
            return h(
                Badge,
                {
                    variant: row.original.priority_badge_color as any,
                },
                () => priorityLabels[priority as keyof typeof priorityLabels],
            );
        },
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
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
            return h(
                Badge,
                {
                    variant: row.original.status_badge_color as any,
                },
                () => statusLabels[status as keyof typeof statusLabels] || status,
            );
        },
    }),
    columnHelper.accessor('requested_by', {
        header: 'Solicitado por',
        cell: ({ row }) => {
            const requestedBy = row.getValue('requested_by') as MaintenanceRequest['requested_by'];
            return h('div', { class: 'text-sm' }, requestedBy.name);
        },
    }),
    columnHelper.accessor('assigned_staff', {
        header: 'Asignado a',
        cell: ({ row }) => {
            const assignedStaff = row.getValue('assigned_staff') as MaintenanceRequest['assigned_staff'];
            return h('div', { class: 'text-sm' }, assignedStaff?.name || 'Sin asignar');
        },
    }),
    columnHelper.accessor('estimated_cost', {
        header: 'Costo Estimado',
        cell: ({ row }) => {
            const cost = row.getValue('estimated_cost') as number;
            return h(
                'div',
                { class: 'text-sm' },
                cost
                    ? new Intl.NumberFormat('es-CO', {
                          style: 'currency',
                          currency: 'COP',
                          minimumFractionDigits: 0,
                      }).format(cost)
                    : 'N/A',
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const request = row.original;
            return h(DropdownAction, {
                align: 'end',
                items: [
                    {
                        label: 'Ver detalles',
                        icon: Eye,
                        href: `/maintenance-requests/${request.id}`,
                    },
                    {
                        label: 'Editar',
                        icon: Edit,
                        href: `/maintenance-requests/${request.id}/edit`,
                    },
                    {
                        label: 'Eliminar',
                        icon: Trash2,
                        href: `/maintenance-requests/${request.id}`,
                        method: 'delete',
                        class: 'text-red-600 focus:text-red-600',
                        confirm: '¿Estás seguro de que quieres eliminar esta solicitud?',
                    },
                ],
            });
        },
    }),
];

const table = useVueTable({
    data: data.value,
    columns,
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    state: {
        sorting: sorting.value,
        columnFilters: columnFilters.value,
        columnVisibility: columnVisibility.value,
        rowSelection: rowSelection.value,
    },
});

const applyFilters = () => {
    router.get(
        '/maintenance-requests',
        {
            search: searchQuery.value,
            status: selectedStatus.value === 'all' ? '' : selectedStatus.value,
            priority: selectedPriority.value === 'all' ? '' : selectedPriority.value,
            category: selectedCategory.value === 'all' ? '' : selectedCategory.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = 'all';
    selectedPriority.value = 'all';
    selectedCategory.value = 'all';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        (selectedStatus.value && selectedStatus.value !== 'all') ||
        (selectedPriority.value && selectedPriority.value !== 'all') ||
        (selectedCategory.value && selectedCategory.value !== 'all')
    );
});

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
];
</script>

<template>
    <Head title="Solicitudes de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Wrench class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Solicitudes de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        v-if="hasPermission('create_maintenance_requests') && hasCategoriesConfigured"
                        :href="route('maintenance-requests.calendar')"
                    >
                        <Button variant="outline">
                            <CalendarDays class="mr-2 h-4 w-4" />
                            Ver Cronograma
                        </Button>
                    </Link>
                    <Link v-if="hasPermission('create_maintenance_requests')" :href="route('maintenance-requests.create')">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Nueva Solicitud
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Setup Wizard (shown when no categories configured) -->
            <SetupWizard
                v-if="needsSetup"
                :has-categories-configured="hasCategoriesConfigured"
                :has-staff-configured="hasStaffConfigured"
                :show-staff-step="true"
            />

            <!-- Metrics/Indicators -->
            <div v-if="!needsSetup" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <!-- Total -->
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ metrics.total }}</p>
                        </div>
                        <Wrench class="h-8 w-8 text-blue-500" />
                    </div>
                </Card>

                <!-- Active -->
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Activas</p>
                            <p class="text-2xl font-bold text-blue-600">{{ metrics.active }}</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                            <span class="text-lg font-bold text-blue-600">•</span>
                        </div>
                    </div>
                </Card>

                <!-- In Progress -->
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">En Progreso</p>
                            <p class="text-2xl font-bold text-green-600">{{ metrics.in_progress }}</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                            <span class="text-sm font-bold text-green-600">▶</span>
                        </div>
                    </div>
                </Card>

                <!-- Critical -->
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Críticas</p>
                            <p class="text-2xl font-bold text-red-600">{{ metrics.critical_priority }}</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                            <span class="text-lg font-bold text-red-600">!</span>
                        </div>
                    </div>
                </Card>

                <!-- Recurring -->
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Recurrentes</p>
                            <p class="text-2xl font-bold text-purple-600">{{ metrics.recurring_active }}</p>
                            <p class="text-xs text-gray-500">{{ metrics.upcoming_recurring }} próximas</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100">
                            <span class="text-lg font-bold text-purple-600">↻</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Filters (hidden when setup is needed) -->
            <Card v-if="!needsSetup" class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Filtros</h3>
                        <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Buscar por título..."
                                    class="pl-9"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
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

                        <div class="space-y-2">
                            <Label for="priority">Prioridad</Label>
                            <Select v-model="selectedPriority">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todas las prioridades" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las prioridades</SelectItem>
                                    <SelectItem value="low">Baja</SelectItem>
                                    <SelectItem value="medium">Media</SelectItem>
                                    <SelectItem value="high">Alta</SelectItem>
                                    <SelectItem value="critical">Crítica</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="category">Categoría</Label>
                            <Select v-model="selectedCategory">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todas las categorías" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las categorías</SelectItem>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end">
                            <Button @click="applyFilters" class="w-full">
                                <Search class="mr-2 h-4 w-4" />
                                Filtrar
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Table (hidden when setup is needed) -->
            <Card v-if="!needsSetup">
                <div class="p-6">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <TableHead
                                        v-for="header in headerGroup.headers"
                                        :key="header.id"
                                        :class="cn('text-left', header.column.getCanSort() && 'cursor-pointer select-none')"
                                        @click="header.column.getToggleSortingHandler()?.($event)"
                                    >
                                        <FlexRender
                                            v-if="!header.isPlaceholder"
                                            :render="header.column.columnDef.header"
                                            :props="header.getContext()"
                                        />
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="table.getRowModel().rows?.length">
                                    <TableRow
                                        v-for="row in table.getRowModel().rows"
                                        :key="row.id"
                                        :data-state="row.getIsSelected() && 'selected'"
                                        class="cursor-pointer hover:bg-gray-50"
                                        @click="router.visit(route('maintenance-requests.show', row.original.id))"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell :colspan="columns.length" class="h-24 text-center">
                                            No se encontraron solicitudes de mantenimiento.
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
