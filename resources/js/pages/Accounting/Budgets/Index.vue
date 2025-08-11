<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
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
import { ChevronDown, ChevronsUpDown, Download, Plus, Search, TrendingUp, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';

export interface Budget {
    id: number;
    name: string;
    description?: string;
    year: number;
    start_date: string;
    end_date: string;
    total_budget: number;
    total_executed: number;
    status: 'Draft' | 'Active' | 'Closed';
    approval_date?: string;
    items_count: number;
    execution_percentage: number;
    created_at: string;
}

const props = defineProps<{
    budgets: {
        data: Budget[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        search?: string;
        status?: string;
        year?: string;
    };
}>();

const data: Budget[] = props.budgets.data;

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    year: props.filters?.year || 'all',
});

// Computed values for filter options
const uniqueYears = computed(() => {
    const years = [...new Set(data.map((budget) => budget.year).filter(year => year != null))].sort((a, b) => b - a);
    return years;
});

const uniqueStatuses = computed(() => {
    return ['Draft', 'Active', 'Closed'];
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
        year: 'all',
    };
    // Also clear table filters
    table.getColumn('name')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (budget) =>
                budget.name?.toLowerCase().includes(searchTerm) ||
                budget.description?.toLowerCase().includes(searchTerm) ||
                budget.year.toString().includes(searchTerm),
        );
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        filtered = filtered.filter((budget) => budget.status === customFilters.value.status);
    }

    // Year filter
    if (customFilters.value.year !== 'all') {
        filtered = filtered.filter((budget) => budget.year.toString() === customFilters.value.year);
    }

    return filtered;
});

const columnHelper = createColumnHelper<Budget>();

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
    columnHelper.accessor('name', {
        enablePinning: true,
        header: 'Nombre del Presupuesto',
        cell: ({ row }) => {
            const budget = row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, budget.name),
                budget.description && h('div', { class: 'text-xs text-muted-foreground truncate max-w-xs' }, budget.description),
            ]);
        },
    }),
    columnHelper.accessor('year', {
        enablePinning: true,
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Año', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('year')),
    }),
    columnHelper.display({
        id: 'period',
        header: 'Período',
        cell: ({ row }) => {
            const budget = row.original;
            const startDate = new Date(budget.start_date).toLocaleDateString('es-CO', { month: 'short', day: 'numeric' });
            const endDate = new Date(budget.end_date).toLocaleDateString('es-CO', { month: 'short', day: 'numeric' });
            return h('div', { class: 'text-sm' }, `${startDate} - ${endDate}`);
        },
    }),
    columnHelper.accessor('total_budget', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Presupuesto Total', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const amount = row.getValue('total_budget') as number;
            return h(
                'div',
                {
                    class: 'text-right font-mono text-sm font-medium',
                },
                `$${(amount || 0).toLocaleString()}`,
            );
        },
    }),
    columnHelper.display({
        id: 'execution',
        header: 'Ejecución',
        cell: ({ row }) => {
            const budget = row.original;
            const percentage = budget.execution_percentage || 0;
            const totalExecuted = budget.total_executed || 0;
            const totalBudget = budget.total_budget || 0;
            const variance = totalExecuted - totalBudget;
            const isOverBudget = variance > 0;

            return h('div', { class: 'space-y-2 min-w-[120px]' }, [
                h('div', { class: 'flex items-center justify-between text-xs' }, [
                    h('span', `${percentage}%`),
                    h('span', { class: 'font-mono' }, `$${totalExecuted.toLocaleString()}`),
                ]),
                h(Progress, {
                    value: Math.min(percentage, 100),
                    class: isOverBudget ? 'bg-red-100' : '',
                }),
                isOverBudget &&
                    h('div', { class: 'text-xs text-red-600 flex items-center gap-1' }, [
                        h(TrendingUp, { class: 'h-3 w-3' }),
                        `+$${variance.toLocaleString()}`,
                    ]),
            ]);
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const statusMap = {
                Draft: { label: 'Borrador', color: 'bg-gray-100 text-gray-800' },
                Active: { label: 'Activo', color: 'bg-green-100 text-green-800' },
                Closed: { label: 'Cerrado', color: 'bg-blue-100 text-blue-800' },
            };
            const statusInfo = statusMap[status] || { label: status, color: 'bg-gray-100 text-gray-800' };
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${statusInfo.color}`,
                },
                statusInfo.label,
            );
        },
    }),
    columnHelper.display({
        id: 'items_count',
        header: 'Partidas',
        cell: ({ row }) => {
            const budget = row.original;
            return h('div', { class: 'text-center text-sm' }, `${budget.items_count} partidas`);
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const budget = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    budget: budget,
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
        title: 'Contabilidad',
        href: '/accounting',
    },
    {
        title: 'Presupuestos',
        href: '/accounting/budgets',
    },
];

const exportBudgets = () => {
    window.location.href = '/accounting/budgets/export';
};
</script>

<template>
    <Head title="Presupuestos" />

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
                                placeholder="Buscar por nombre, descripción o año..."
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
                                    <SelectItem value="Draft">Borrador</SelectItem>
                                    <SelectItem value="Active">Activo</SelectItem>
                                    <SelectItem value="Closed">Cerrado</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_year">Año</Label>
                            <Select v-model="customFilters.year">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los años" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los años</SelectItem>
                                    <SelectItem v-for="year in uniqueYears" :key="year" :value="year.toString()">
                                        {{ year }}
                                    </SelectItem>
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
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} presupuestos</div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por nombre..."
                    :model-value="table.getColumn('name')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('name')?.setFilterValue($event)"
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

                <Button @click="router.visit('/accounting/budgets/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Presupuesto
                </Button>

                <Button variant="outline" @click="exportBudgets">
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
                                    @click="router.visit(`/accounting/budgets/${row.original.id}`)"
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
