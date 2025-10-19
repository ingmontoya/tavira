<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, ExpandedState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, Download, Plus, Search, Vote, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../utils';

export interface Assembly {
    id: number;
    title: string;
    description: string;
    type: string;
    status: 'scheduled' | 'in_progress' | 'closed' | 'cancelled';
    scheduled_at: string;
    started_at?: string;
    ended_at?: string;
    required_quorum_percentage: number;
    duration_minutes?: number;
    is_active: boolean;
    can_vote: boolean;
    quorum_status: {
        total_apartments: number;
        participating_apartments: number;
        quorum_percentage: number;
        required_quorum_percentage: number;
        has_quorum: boolean;
    };
    status_badge: {
        text: string;
        class: string;
    };
    votes_count?: number;
    delegates_count?: number;
    documents?: any[];
    creator?: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    assemblies: {
        data: Assembly[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        search?: string;
        status?: string;
        type?: string;
    };
}>();

const data: Assembly[] = props.assemblies.data;

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    type: props.filters?.type || 'all',
});

// Computed values for filter options
const uniqueStatuses = computed(() => {
    return ['scheduled', 'in_progress', 'closed', 'cancelled'];
});

const uniqueTypes = computed(() => {
    return ['ordinary', 'extraordinary', 'budget', 'other'];
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        status: 'all',
        type: 'all',
    };
    table.getColumn('title')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (assembly) =>
                assembly.title?.toLowerCase().includes(searchTerm) ||
                assembly.description?.toLowerCase().includes(searchTerm) ||
                assembly.type?.toLowerCase().includes(searchTerm) ||
                assembly.creator?.name?.toLowerCase().includes(searchTerm),
        );
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        filtered = filtered.filter((assembly) => assembly.status === customFilters.value.status);
    }

    // Type filter
    if (customFilters.value.type !== 'all') {
        filtered = filtered.filter((assembly) => assembly.type === customFilters.value.type);
    }

    return filtered;
});

const columnHelper = createColumnHelper<Assembly>();

const columns = [
    columnHelper.display({
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                modelValue: table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }) => {
            return h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            });
        },
        enableSorting: false,
        enableHiding: false,
    }),
    columnHelper.accessor('title', {
        enablePinning: true,
        header: 'Título',
        cell: ({ row }) => {
            const assembly = row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, assembly.title),
                h('div', { class: 'text-xs text-muted-foreground truncate max-w-xs' }, assembly.description),
            ]);
        },
    }),
    columnHelper.accessor('type', {
        enablePinning: true,
        header: 'Tipo',
        cell: ({ row }) => {
            const type = row.getValue('type') as string;
            const typeMap = {
                ordinary: 'Ordinaria',
                extraordinary: 'Extraordinaria',
                budget: 'Presupuestal',
                other: 'Otra',
            };
            return h('span', { class: 'capitalize' }, typeMap[type] || type);
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const assembly = row.original;
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${assembly.status_badge.class}`,
                },
                assembly.status_badge.text,
            );
        },
    }),
    columnHelper.accessor('scheduled_at', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Fecha Programada', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const scheduledAt = row.getValue('scheduled_at') as string;
            const date = new Date(scheduledAt);
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'text-sm' }, date.toLocaleDateString('es-CO')),
                h(
                    'div',
                    { class: 'text-xs text-muted-foreground' },
                    date.toLocaleTimeString('es-CO', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                ),
            ]);
        },
    }),
    columnHelper.display({
        id: 'quorum',
        header: 'Quórum',
        cell: ({ row }) => {
            const assembly = row.original;
            const quorum = assembly.quorum_status;
            const percentage = quorum.quorum_percentage;
            const hasQuorum = quorum.has_quorum;

            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'text-sm' }, `${percentage.toFixed(1)}%`),
                h(
                    'div',
                    {
                        class: `text-xs ${hasQuorum ? 'text-green-600' : 'text-red-600'}`,
                    },
                    `${quorum.participating_apartments}/${quorum.total_apartments}`,
                ),
            ]);
        },
    }),
    columnHelper.display({
        id: 'votes',
        header: 'Votaciones',
        cell: ({ row }) => {
            const assembly = row.original;
            return h('div', { class: 'flex items-center gap-2' }, [
                h(Vote, { class: 'h-4 w-4 text-muted-foreground' }),
                h('span', { class: 'text-sm' }, assembly.votes_count || 0),
            ]);
        },
    }),
    columnHelper.display({
        id: 'duration',
        header: 'Duración',
        cell: ({ row }) => {
            const assembly = row.original;
            if (!assembly.duration_minutes) {
                return h('span', { class: 'text-muted-foreground text-sm' }, '-');
            }
            const hours = Math.floor(assembly.duration_minutes / 60);
            const minutes = assembly.duration_minutes % 60;
            return h('div', { class: 'text-sm' }, hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`);
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const assembly = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    assembly: assembly,
                }),
            );
        },
    },
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const expanded = ref<ExpandedState>({});

const table = useVueTable({
    get data() {
        return filteredData.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expanded),

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
        get rowSelection() {
            return rowSelection.value;
        },
        get expanded() {
            return expanded.value;
        },
        columnPinning: {
            left: ['status'],
        },
    },
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Asambleas',
        href: '/assemblies',
    },
];

const exportAssemblies = () => {
    window.location.href = '/assemblies/export';
};
</script>

<template>
    <Head title="Asambleas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Filtros Avanzados -->
            <Card class="mb-4 p-4">
                <div class="space-y-4">
                    <!-- Búsqueda General -->
                    <div>
                        <Label for="search">Búsqueda General</Label>
                        <div class="relative mt-3">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="customFilters.search"
                                placeholder="Buscar por título, descripción, tipo..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="scheduled">Programada</SelectItem>
                                    <SelectItem value="in_progress">En Curso</SelectItem>
                                    <SelectItem value="closed">Cerrada</SelectItem>
                                    <SelectItem value="cancelled">Cancelada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_type">Tipo</Label>
                            <Select v-model="customFilters.type">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los tipos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="ordinary">Ordinaria</SelectItem>
                                    <SelectItem value="extraordinary">Extraordinaria</SelectItem>
                                    <SelectItem value="budget">Presupuestal</SelectItem>
                                    <SelectItem value="other">Otra</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} asambleas</div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por título..."
                    :model-value="table.getColumn('title')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('title')?.setFilterValue($event)"
                />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="ml-auto">
                            Columnas
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuCheckboxItem
                            v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                            :key="column.id"
                            class="capitalize"
                            :model-value="column.getIsVisible()"
                            @update:model-value="
                                (value) => {
                                    column.toggleVisibility(!!value);
                                }
                            "
                        >
                            {{ column.id }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button @click="router.visit('/assemblies/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Asamblea
                </Button>

                <Button variant="outline" @click="exportAssemblies">
                    <Download class="mr-2 h-4 w-4" />
                    Exportar
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                :data-pinned="header.column.getIsPinned()"
                                :class="
                                    cn(
                                        { 'sticky bg-background/95': header.column.getIsPinned() },
                                        header.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                    )
                                "
                            >
                                <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <template v-for="row in table.getRowModel().rows" :key="row.id">
                                <TableRow
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="cursor-pointer transition-colors hover:bg-muted/50"
                                    @click="router.visit(`/assemblies/${row.original.id}`)"
                                >
                                    <TableCell
                                        v-for="cell in row.getVisibleCells()"
                                        :key="cell.id"
                                        :data-pinned="cell.column.getIsPinned()"
                                        :class="
                                            cn(
                                                { 'sticky bg-background/95': cell.column.getIsPinned() },
                                                cell.column.getIsPinned() === 'left' ? 'left-0' : 'right-0',
                                                cell.column.id === 'select' || cell.column.id === 'actions' ? 'cursor-default' : '',
                                            )
                                        "
                                        @click="cell.column.id === 'select' || cell.column.id === 'actions' ? $event.stopPropagation() : null"
                                    >
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                        </template>

                        <TableRow v-else>
                            <TableCell :colspan="columns.length" class="h-24 text-center"> Sin Resultados. </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="flex items-center justify-end space-x-2 py-4">
                <div class="flex-1 text-sm text-muted-foreground">
                    {{ table.getFilteredSelectedRowModel().rows.length }} de {{ table.getFilteredRowModel().rows.length }} fila(s) seleccionadas.
                </div>
                <div class="space-x-2">
                    <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()"> Anterior </Button>
                    <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()"> Siguiente </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
