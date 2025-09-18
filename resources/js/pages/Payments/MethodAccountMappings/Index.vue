<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { AlertTriangle, ChevronDown, ChevronsUpDown, Database, Edit, Eye, Filter, Plus, Search, Settings, ToggleLeft, ToggleRight, Trash2, X } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { valueUpdater } from '../../../utils';

interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    type: string;
    subtype?: string;
}

interface PaymentMethodAccountMapping {
    id: number;
    payment_method: string;
    payment_method_label: string;
    cash_account_id: number;
    cash_account: ChartOfAccount;
    is_active: boolean;
}

const props = defineProps<{
    mappings: {
        data: PaymentMethodAccountMapping[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters?: {
        search?: string;
        payment_method?: string;
        is_active?: boolean;
    };
    has_chart_of_accounts: boolean;
    has_mappings: boolean;
    mappings_count: number;
}>();

const data = computed(() => props.mappings.data);

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    payment_method: props.filters?.payment_method || 'all',
    is_active: props.filters?.is_active !== undefined ? props.filters.is_active.toString() : 'all',
});

// Watch for changes in props.filters to sync with server state
watch(
    () => props.filters,
    (newFilters) => {
        let newCustomFilters;

        if (newFilters && Object.keys(newFilters).length > 0) {
            newCustomFilters = {
                search: newFilters.search || '',
                payment_method: newFilters.payment_method || 'all',
                is_active: newFilters.is_active !== undefined ? newFilters.is_active.toString() : 'all',
            };
        } else {
            // No filters from server, reset to defaults
            newCustomFilters = {
                search: '',
                payment_method: 'all',
                is_active: 'all',
            };
        }

        customFilters.value = newCustomFilters;
    },
    { immediate: true, deep: true },
);

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

// Column helper
const columnHelper = createColumnHelper<PaymentMethodAccountMapping>();

const columns = [
    columnHelper.display({
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                checked: table.getIsAllPageRowsSelected(),
                indeterminate: table.getIsSomePageRowsSelected(),
                'onUpdate:checked': (value) => table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                checked: row.getIsSelected(),
                'onUpdate:checked': (value) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    }),
    columnHelper.accessor('payment_method_label', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Método de Pago', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const mapping = row.original;
            const paymentMethodColors = {
                cash: 'bg-green-100 text-green-800',
                bank_transfer: 'bg-blue-100 text-blue-800',
                check: 'bg-purple-100 text-purple-800',
                credit_card: 'bg-orange-100 text-orange-800',
                debit_card: 'bg-yellow-100 text-yellow-800',
                online: 'bg-indigo-100 text-indigo-800',
                pse: 'bg-pink-100 text-pink-800',
                other: 'bg-gray-100 text-gray-800',
            };
            return h(
                Badge,
                {
                    class: paymentMethodColors[mapping.payment_method as keyof typeof paymentMethodColors] || 'bg-gray-100 text-gray-800',
                },
                () => mapping.payment_method_label,
            );
        },
    }),
    columnHelper.accessor('cash_account', {
        header: 'Cuenta Contable',
        cell: ({ row }) => {
            const account = row.original.cash_account;
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, `${account.code} - ${account.name}`),
                h('span', { class: 'text-xs text-muted-foreground' }, `${account.type} / ${account.subtype || ''}`),
            ]);
        },
    }),
    columnHelper.accessor('is_active', {
        header: 'Estado',
        cell: ({ row }) => {
            const mapping = row.original;
            return h(
                Badge,
                {
                    class: mapping.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                },
                () => (mapping.is_active ? 'Activo' : 'Inactivo'),
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const mapping = row.original;
            const actions = [
                {
                    label: 'Ver Detalle',
                    icon: Eye,
                    href: `/payment-method-account-mappings/${mapping.id}`,
                },
                {
                    label: 'Editar',
                    icon: Edit,
                    href: `/payment-method-account-mappings/${mapping.id}/edit`,
                },
                {
                    label: mapping.is_active ? 'Desactivar' : 'Activar',
                    icon: mapping.is_active ? ToggleLeft : ToggleRight,
                    action: () => toggleMapping(mapping.id),
                },
                {
                    label: 'Eliminar',
                    icon: Trash2,
                    action: () => deleteMapping(mapping.id),
                    destructive: true,
                },
            ];
            return h(DropdownAction, { actions });
        },
    }),
];

const table = useVueTable({
    data: data.value,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    state: {
        sorting: sorting.value,
        columnFilters: columnFilters.value,
        columnVisibility: columnVisibility.value,
        rowSelection: rowSelection.value,
    },
});

// Apply filters function for server-side filtering
const applyFilters = () => {
    const filterData: Record<string, string> = {};

    // Add filters to data object
    if (customFilters.value.search && customFilters.value.search.trim()) {
        filterData.search = customFilters.value.search.trim();
    }
    if (customFilters.value.payment_method && customFilters.value.payment_method !== 'all') {
        filterData.payment_method = customFilters.value.payment_method;
    }
    if (customFilters.value.is_active && customFilters.value.is_active !== 'all') {
        filterData.is_active = customFilters.value.is_active;
    }

    router.get('/payment-method-account-mappings', filterData, {
        preserveState: true,
        preserveScroll: true,
        only: ['mappings', 'filters'],
    });
};

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    router.get(
        '/payment-method-account-mappings',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['mappings', 'filters'],
        },
    );
};

// Toggle mapping status
const toggleMapping = (id: number) => {
    router.post(
        `/payment-method-account-mappings/${id}/toggle`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Delete mapping
const deleteMapping = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este mapeo de cuenta?')) {
        router.delete(`/payment-method-account-mappings/${id}`);
    }
};

// Pagination functions
const nextPage = () => {
    const currentPage = props.mappings.current_page;
    if (currentPage < props.mappings.last_page) {
        const filterData = { ...customFilters.value, page: (currentPage + 1).toString() };
        // Clean up 'all' values
        Object.keys(filterData).forEach((key) => {
            if (filterData[key] === 'all' || filterData[key] === '') {
                delete filterData[key];
            }
        });
        router.get('/payment-method-account-mappings', filterData, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const previousPage = () => {
    const currentPage = props.mappings.current_page;
    if (currentPage > 1) {
        const filterData = { ...customFilters.value, page: (currentPage - 1).toString() };
        // Clean up 'all' values
        Object.keys(filterData).forEach((key) => {
            if (filterData[key] === 'all' || filterData[key] === '') {
                delete filterData[key];
            }
        });
        router.get('/payment-method-account-mappings', filterData, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Payment method options
const paymentMethodOptions = [
    { value: 'all', label: 'Todos los métodos' },
    { value: 'cash', label: 'Efectivo' },
    { value: 'bank_transfer', label: 'Transferencia Bancaria' },
    { value: 'check', label: 'Cheque' },
    { value: 'credit_card', label: 'Tarjeta de Crédito' },
    { value: 'debit_card', label: 'Tarjeta Débito' },
    { value: 'online', label: 'Pago en Línea' },
    { value: 'pse', label: 'PSE' },
    { value: 'other', label: 'Otro' },
];

// Status options
const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'true', label: 'Activos' },
    { value: 'false', label: 'Inactivos' },
];

// Create default mappings
const createDefaultMappings = () => {
    router.post('/payment-method-account-mappings/create-defaults');
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Mapeo de Cuentas de Pago',
        href: '/payment-method-account-mappings',
    },
];
</script>

<template>
    <Head title="Mapeo de Cuentas de Pago" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Mapeo de Cuentas de Pago</h1>
                    <p class="text-muted-foreground">Configura qué cuenta contable se usa para cada método de pago</p>
                </div>
                <div class="flex space-x-2">
                    <Button asChild variant="outline">
                        <Link href="/payments">
                            <Settings class="mr-2 h-4 w-4" />
                            Volver a Pagos
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href="/payment-method-account-mappings/create">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Mapeo
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- System Readiness Alerts -->
            <div class="space-y-4">
                <!-- No Chart of Accounts Alert -->
                <Alert v-if="!has_chart_of_accounts" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium">Plan de cuentas no configurado</p>
                            <p>Debe crear el plan de cuentas antes de configurar mapeos de métodos de pago.</p>
                            <Button asChild variant="outline" size="sm" class="mt-2">
                                <Link href="/chart-of-accounts">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Configurar Plan de Cuentas
                                </Link>
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- No Mappings Alert -->
                <Alert v-else-if="!has_mappings" class="bg-blue-50 border-blue-200">
                    <Database class="h-4 w-4 text-blue-600" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-medium text-blue-800">Mapeos de métodos de pago no configurados</p>
                            <p class="text-blue-700">Configure qué cuenta contable se usa para cada método de pago (efectivo, transferencia, etc.).</p>
                            <Button @click="createDefaultMappings" variant="default" size="sm" class="mt-3">
                                <Settings class="mr-2 h-4 w-4" />
                                Crear Mapeos por Defecto
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Filters -->
            <Card class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-4 w-4" />
                        <Label class="text-sm font-medium">Filtros</Label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    placeholder="Método de pago o cuenta..."
                                    v-model="customFilters.search"
                                    class="pl-8"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <!-- Payment Method Filter -->
                        <div class="space-y-2">
                            <Label>Método de Pago</Label>
                            <Select v-model="customFilters.payment_method">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar método" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="method in paymentMethodOptions" :key="method.value" :value="method.value">
                                        {{ method.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <Select v-model="customFilters.is_active">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            <Button @click="applyFilters" class="gap-2">
                                <Search class="h-4 w-4" />
                                Aplicar Filtros
                            </Button>
                            <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                                <X class="mr-2 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ props.mappings.from || 0 }} - {{ props.mappings.to || 0 }} de {{ props.mappings.total || 0 }} mapeos
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Table -->
            <Card>
                <div class="p-6">
                    <!-- Table controls -->
                    <div class="flex items-center gap-2 pb-4">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
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
                    </div>

                    <!-- Table -->
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <TableHead v-for="header in headerGroup.headers" :key="header.id">
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
                                        :data-state="row.getIsSelected() ? 'selected' : undefined"
                                        class="cursor-pointer transition-colors hover:bg-muted/50"
                                        @click="router.visit(`/payment-method-account-mappings/${row.original.id}`)"
                                    >
                                        <TableCell
                                            v-for="cell in row.getVisibleCells()"
                                            :key="cell.id"
                                            :class="cell.column.id === 'select' || cell.column.id === 'actions' ? 'cursor-default' : ''"
                                            @click="cell.column.id === 'select' || cell.column.id === 'actions' ? $event.stopPropagation() : null"
                                        >
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell :colSpan="columns.length" class="h-24 text-center">
                                            No se encontraron mapeos de cuentas de pago.
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between space-x-2 pt-4">
                        <div class="flex-1 text-sm text-muted-foreground">
                            {{ table.getFilteredSelectedRowModel().rows.length }} de {{ props.mappings.total }} fila(s) seleccionadas.
                        </div>
                        <div class="flex items-center space-x-6 lg:space-x-8">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium">Página</p>
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium">{{ props.mappings.current_page }}</span>
                                    <span class="text-sm text-muted-foreground">de {{ props.mappings.last_page }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Button variant="outline" size="sm" :disabled="props.mappings.current_page <= 1" @click="previousPage">
                                    Anterior
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="props.mappings.current_page >= props.mappings.last_page"
                                    @click="nextPage"
                                >
                                    Siguiente
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
