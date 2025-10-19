<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { Eye, MessageSquare, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../utils';

export interface SupportTicket {
    id: number;
    title: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    category: 'technical' | 'billing' | 'general' | 'feature_request' | 'bug_report';
    created_at: string;
    resolved_at?: string;
    user: {
        id: number;
        name: string;
    };
    assigned_to?: {
        id: number;
        name: string;
    };
    latest_message?: {
        id: number;
        message: string;
        created_at: string;
        user: {
            name: string;
        };
    }[];
}

const props = defineProps<{
    tickets: {
        data: SupportTicket[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        status?: string;
        priority?: string;
        category?: string;
    };
    canManage: boolean;
}>();

// Navigation helpers
const navigateTo = (url: string) => router.visit(url);

const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'open', label: 'Abierto' },
    { value: 'in_progress', label: 'En progreso' },
    { value: 'resolved', label: 'Resuelto' },
    { value: 'closed', label: 'Cerrado' },
];

const priorityOptions = [
    { value: 'all', label: 'Todas las prioridades' },
    { value: 'low', label: 'Baja' },
    { value: 'medium', label: 'Media' },
    { value: 'high', label: 'Alta' },
    { value: 'urgent', label: 'Urgente' },
];

const categoryOptions = [
    { value: 'all', label: 'Todas las categorías' },
    { value: 'technical', label: 'Técnico' },
    { value: 'billing', label: 'Facturación' },
    { value: 'general', label: 'General' },
    { value: 'feature_request', label: 'Solicitud de función' },
    { value: 'bug_report', label: 'Reporte de error' },
];

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'open':
            return 'default';
        case 'in_progress':
            return 'secondary';
        case 'resolved':
            return 'outline';
        case 'closed':
            return 'destructive';
        default:
            return 'default';
    }
};

const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
        case 'low':
            return 'secondary';
        case 'medium':
            return 'default';
        case 'high':
            return 'outline';
        case 'urgent':
            return 'destructive';
        default:
            return 'default';
    }
};

const getCategoryLabel = (category: string) => {
    const option = categoryOptions.find((o) => o.value === category);
    return option?.label || category;
};

const columnHelper = createColumnHelper<SupportTicket>();

const columns = [
    columnHelper.accessor('id', {
        header: '#',
        cell: (info) => info.getValue(),
        size: 80,
    }),
    columnHelper.accessor('title', {
        header: 'Título',
        cell: (info) =>
            h(
                Link,
                {
                    href: `/support/${info.row.original.id}`,
                    class: 'hover:underline font-medium',
                },
                info.getValue(),
            ),
        size: 300,
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: (info) =>
            h(
                Badge,
                {
                    variant: getStatusBadgeVariant(info.getValue()),
                },
                {
                    default: () =>
                        info.getValue() === 'open'
                            ? 'Abierto'
                            : info.getValue() === 'in_progress'
                              ? 'En progreso'
                              : info.getValue() === 'resolved'
                                ? 'Resuelto'
                                : 'Cerrado',
                },
            ),
        size: 120,
    }),
    columnHelper.accessor('priority', {
        header: 'Prioridad',
        cell: (info) =>
            h(
                Badge,
                {
                    variant: getPriorityBadgeVariant(info.getValue()),
                },
                {
                    default: () =>
                        info.getValue() === 'low' ? 'Baja' : info.getValue() === 'medium' ? 'Media' : info.getValue() === 'high' ? 'Alta' : 'Urgente',
                },
            ),
        size: 100,
    }),
    columnHelper.accessor('category', {
        header: 'Categoría',
        cell: (info) => getCategoryLabel(info.getValue()),
        size: 150,
    }),
    columnHelper.accessor('user.name', {
        header: 'Usuario',
        cell: (info) => info.getValue(),
        size: 150,
    }),
    columnHelper.accessor('created_at', {
        header: 'Creado',
        cell: (info) => new Date(info.getValue()).toLocaleDateString('es-CO'),
        size: 120,
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const ticket = row.original;
            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'sm',
                        onClick: () => navigateTo(`/support/${ticket.id}`),
                    },
                    {
                        default: () => [h(Eye, { class: 'w-4 h-4' })],
                    },
                ),
            ]);
        },
        size: 100,
    }),
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});

const table = useVueTable({
    get data() {
        return props.tickets.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
    },
});

const selectedStatus = ref(props.filters.status || 'all');
const selectedPriority = ref(props.filters.priority || 'all');
const selectedCategory = ref(props.filters.category || 'all');

const applyFilters = () => {
    const params = new URLSearchParams();
    if (selectedStatus.value !== 'all') params.append('status', selectedStatus.value);
    if (selectedPriority.value !== 'all') params.append('priority', selectedPriority.value);
    if (selectedCategory.value !== 'all') params.append('category', selectedCategory.value);

    router.get(route('support.index'), Object.fromEntries(params));
};

const clearFilters = () => {
    selectedStatus.value = 'all';
    selectedPriority.value = 'all';
    selectedCategory.value = 'all';
    router.get(route('support.index'));
};

const hasActiveFilters = computed(() => selectedStatus.value !== 'all' || selectedPriority.value !== 'all' || selectedCategory.value !== 'all');
</script>

<template>
    <Head title="Tickets de Soporte" />

    <AppLayout>
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Tickets de Soporte</h1>
                    <p class="mt-1 text-sm text-gray-600">Gestiona y realiza seguimiento de tickets de soporte</p>
                </div>
                <Button @click="navigateTo('/support/create')" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Crear Ticket
                </Button>
            </div>

            <!-- Filtros -->
            <Card class="mb-6 p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <Label for="status" class="text-sm font-medium">Estado</Label>
                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="priority" class="text-sm font-medium">Prioridad</Label>
                        <Select v-model="selectedPriority">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar prioridad" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="category" class="text-sm font-medium">Categoría</Label>
                        <Select v-model="selectedCategory">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in categoryOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters" class="flex-1">
                            <Search class="mr-2 h-4 w-4" />
                            Filtrar
                        </Button>
                        <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters" class="px-3">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Tabla de tickets -->
            <Card>
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead
                                    v-for="header in headerGroup.headers"
                                    :key="header.id"
                                    :class="cn('px-4 py-3', header.column.getCanSort() && 'cursor-pointer select-none')"
                                    @click="header.column.getCanSort() ? header.column.getToggleSortingHandler()?.() : undefined"
                                >
                                    <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length">
                                <TableRow
                                    v-for="row in table.getRowModel().rows"
                                    :key="row.id"
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="hover:bg-muted/50"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-4 py-3">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <MessageSquare class="mb-2 h-8 w-8" />
                                            <p class="text-sm">No se encontraron tickets de soporte</p>
                                            <p class="mt-1 text-xs">Crea tu primer ticket para comenzar</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>

                <!-- Paginación -->
                <div class="flex items-center justify-between space-x-2 px-4 py-4" v-if="props.tickets.last_page > 1">
                    <div class="text-sm text-muted-foreground">Mostrando {{ props.tickets.data.length }} de {{ props.tickets.total }} resultados</div>
                    <div class="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="props.tickets.current_page === 1"
                            @click="router.get(route('support.index'), { ...props.filters, page: props.tickets.current_page - 1 })"
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="props.tickets.current_page === props.tickets.last_page"
                            @click="router.get(route('support.index'), { ...props.filters, page: props.tickets.current_page + 1 })"
                        >
                            Siguiente
                        </Button>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
