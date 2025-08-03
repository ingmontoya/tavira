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
import { ChevronDown, ChevronsUpDown, Download, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';

export interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    account_type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
    parent_account_id?: number;
    parent_account?: {
        code: string;
        name: string;
    };
    is_active: boolean;
    balance: number;
    description?: string;
    created_at: string;
}

const props = defineProps<{
    accounts: {
        data: ChartOfAccount[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        search?: string;
        account_type?: string;
        status?: string;
    };
}>();

const data: ChartOfAccount[] = props.accounts.data;

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    account_type: props.filters?.account_type || 'all',
    status: props.filters?.status || 'all',
});

// Computed values for filter options
const uniqueAccountTypes = computed(() => {
    return ['asset', 'liability', 'equity', 'income', 'expense'];
});

const uniqueStatuses = computed(() => {
    return ['Active', 'Inactive'];
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        account_type: 'all',
        status: 'all',
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
            (account) =>
                account.code?.toLowerCase().includes(searchTerm) ||
                account.name?.toLowerCase().includes(searchTerm) ||
                account.description?.toLowerCase().includes(searchTerm) ||
                account.parent_account?.name?.toLowerCase().includes(searchTerm),
        );
    }

    // Account type filter
    if (customFilters.value.account_type !== 'all') {
        filtered = filtered.filter((account) => account.account_type === customFilters.value.account_type);
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        const isActive = customFilters.value.status === 'Active';
        filtered = filtered.filter((account) => account.is_active === isActive);
    }

    return filtered;
});

const columnHelper = createColumnHelper<ChartOfAccount>();

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
    columnHelper.accessor('code', {
        enablePinning: true,
        header: 'Código',
        cell: ({ row }) => h('div', { class: 'font-mono text-sm' }, row.getValue('code')),
    }),
    columnHelper.accessor('name', {
        enablePinning: true,
        header: 'Nombre de la Cuenta',
        cell: ({ row }) => {
            const account = row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, account.name),
                account.parent_account && h('div', { class: 'text-xs text-muted-foreground' }, `Subcuenta de: ${account.parent_account.name}`),
            ]);
        },
    }),
    columnHelper.accessor('account_type', {
        enablePinning: true,
        header: 'Tipo',
        cell: ({ row }) => {
            const type = row.getValue('account_type') as string;
            const typeMap = {
                asset: 'Activo',
                liability: 'Pasivo',
                equity: 'Patrimonio',
                income: 'Ingreso',
                expense: 'Gasto',
            };
            const colorMap = {
                asset: 'bg-blue-100 text-blue-800',
                liability: 'bg-red-100 text-red-800',
                equity: 'bg-purple-100 text-purple-800',
                income: 'bg-green-100 text-green-800',
                expense: 'bg-orange-100 text-orange-800',
            };
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${colorMap[type]}`,
                },
                typeMap[type] || type,
            );
        },
    }),
    columnHelper.accessor('balance', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Saldo', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const balance = row.getValue('balance') as number | null | undefined;
            const numericBalance = balance ?? 0;
            const isNegative = numericBalance < 0;
            return h(
                'div',
                {
                    class: `text-right font-mono ${isNegative ? 'text-red-600' : 'text-green-600'}`,
                },
                `$${Math.abs(numericBalance).toLocaleString()}`,
            );
        },
    }),
    columnHelper.accessor('is_active', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                        isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`,
                },
                isActive ? 'Activa' : 'Inactiva',
            );
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const account = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    account: account,
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
            left: ['is_active'],
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
        title: 'Plan de Cuentas',
        href: '/accounting/chart-of-accounts',
    },
];

const exportAccounts = () => {
    window.location.href = '/accounting/chart-of-accounts/export';
};
</script>

<template>
    <Head title="Plan de Cuentas" />

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
                                placeholder="Buscar por código, nombre de cuenta, descripción..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_account_type">Tipo de Cuenta</Label>
                            <Select v-model="customFilters.account_type">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los tipos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="asset">Activo</SelectItem>
                                    <SelectItem value="liability">Pasivo</SelectItem>
                                    <SelectItem value="equity">Patrimonio</SelectItem>
                                    <SelectItem value="income">Ingreso</SelectItem>
                                    <SelectItem value="expense">Gasto</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="Active">Activa</SelectItem>
                                    <SelectItem value="Inactive">Inactiva</SelectItem>
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
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} cuentas</div>
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

                <Button @click="router.visit('/accounting/chart-of-accounts/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Cuenta
                </Button>

                <Button variant="outline" @click="exportAccounts">
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
                                    @click="router.visit(`/accounting/chart-of-accounts/${row.original.id}`)"
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
