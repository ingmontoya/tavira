<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
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
import { ChevronDown, ChevronsUpDown, Database, Download, Plus, Search, Settings, X } from 'lucide-vue-next';
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
    has_accounts: boolean;
    accounts_count: number;
    needs_sync: boolean;
}>();

const data: ChartOfAccount[] = props.accounts.data;
const { error, success } = useToast();

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
    return ['active', 'inactive'];
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Apply filters - send to server
const applyFilters = () => {
    const filters: Record<string, any> = {};

    if (customFilters.value.search) {
        filters.search = customFilters.value.search;
    }

    if (customFilters.value.account_type !== 'all') {
        filters.account_type = customFilters.value.account_type;
    }

    if (customFilters.value.status !== 'all') {
        filters.status = customFilters.value.status;
    }

    router.get('/accounting/chart-of-accounts', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        account_type: 'all',
        status: 'all',
    };
    // Also clear table filters
    table.getColumn('name')?.setFilterValue('');
    // Apply cleared filters
    router.get('/accounting/chart-of-accounts', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Create default accounts
const createDefaultAccounts = () => {
    router.post(
        '/accounting/chart-of-accounts/create-defaults',
        {},
        {
            onSuccess: () => {
                success('Plan de cuentas creado exitosamente');
                // Use setTimeout to ensure toast shows first, then reload
                setTimeout(() => {
                    router.reload();
                }, 100);
            },
            onError: (errors) => {
                const errorMessage = errors?.create_defaults || 'Error al crear el plan de cuentas';
                error(errorMessage);
            },
        },
    );
};

// Sync accounts
const syncAccounts = () => {
    router.post(
        '/accounting/chart-of-accounts/sync',
        {},
        {
            onSuccess: () => {
                success('Plan de cuentas sincronizado exitosamente');
                // Use setTimeout to ensure toast shows first, then reload
                setTimeout(() => {
                    router.reload();
                }, 100);
            },
            onError: (errors) => {
                const errorMessage = errors?.sync || 'Error al sincronizar el plan de cuentas';
                error(errorMessage);
            },
        },
    );
};

// Server-side filtering is handled by the backend
// All filters are applied via applyFilters() which makes a server request

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
        return data;
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
            <!-- Alert for no accounts -->
            <Alert v-if="!has_accounts" class="border-blue-200 bg-blue-50">
                <Database class="h-4 w-4 text-blue-600" />
                <AlertDescription>
                    <div class="space-y-2">
                        <p class="font-medium text-blue-800">Plan de cuentas no configurado</p>
                        <p class="text-blue-700">
                            Para comenzar a usar el sistema contable, debe inicializar el plan de cuentas con las cuentas estándar colombianas.
                        </p>
                        <Button @click="createDefaultAccounts" variant="default" size="sm" class="mt-3">
                            <Settings class="mr-2 h-4 w-4" />
                            Crear Plan de Cuentas por Defecto
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <Alert v-if="needs_sync" class="border-amber-200 bg-amber-50">
                <Database class="h-4 w-4 text-amber-600" />
                <AlertDescription>
                    <div class="space-y-2">
                        <p class="font-medium text-amber-800">Plan de cuentas incompleto</p>
                        <p class="text-amber-700">
                            Tienes {{ accounts_count }} cuentas de 316 esperadas. Sincroniza el plan de cuentas para agregar las cuentas faltantes del
                            PUC colombiano para propiedad horizontal.
                        </p>
                        <Button @click="syncAccounts" variant="default" size="sm" class="mt-3">
                            <Settings class="mr-2 h-4 w-4" />
                            Sincronizar Plan de Cuentas
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

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
                                    <SelectItem value="active">Activa</SelectItem>
                                    <SelectItem value="inactive">Inactiva</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            <Button @click="applyFilters">
                                <Search class="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                            <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                                <X class="mr-2 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        </div>
                        <div class="text-sm text-muted-foreground">Mostrando {{ props.accounts.from }} - {{ props.accounts.to }} de {{ props.accounts.total }} cuentas</div>
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

            <div class="flex items-center justify-between space-x-2 py-4">
                <div class="flex-1 text-sm text-muted-foreground">
                    Mostrando {{ accounts.from }} a {{ accounts.to }} de {{ accounts.total }} cuentas
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" size="sm" :disabled="!accounts.prev_page_url" @click="router.visit(accounts.prev_page_url)">
                        Anterior
                    </Button>
                    <Button variant="outline" size="sm" :disabled="!accounts.next_page_url" @click="router.visit(accounts.next_page_url)">
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
