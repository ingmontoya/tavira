<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { CheckCircle, ChevronDown, ChevronsUpDown, Edit, Eye, Filter, Plus, Receipt, Search, Trash2, X, XCircle } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { cn, valueUpdater } from '../../../utils';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Facturas',
        href: '/invoices',
    },
];

interface Invoice {
    id: number;
    invoice_number: string;
    apartment: {
        id: number;
        number: string;
        tower: string;
        full_address: string;
    };
    type: string;
    type_label: string;
    billing_date: string;
    due_date: string;
    billing_period_label: string;
    total_amount: number;
    paid_amount: number;
    balance_due: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    days_overdue?: number;
    items: Array<{
        id: number;
        description: string;
        quantity: number;
        unit_price: number;
        total_price: number;
    }>;
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    full_address: string;
}

const props = defineProps<{
    invoices: {
        data: Invoice[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    apartments: Apartment[];
    filters?: {
        search?: string;
        status?: string;
        type?: string;
        apartment_id?: string;
        period?: string;
    };
}>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const data = computed(() => props.invoices.data);

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    type: props.filters?.type || 'all',
    apartment_id: props.filters?.apartment_id || 'all',
    period: props.filters?.period || '',
});

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

// Column helper
const columnHelper = createColumnHelper<Invoice>();

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
    columnHelper.accessor('invoice_number', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Número', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const invoice = row.original;
            return h('div', { class: 'font-medium' }, invoice.invoice_number);
        },
    }),
    columnHelper.accessor('apartment.full_address', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const apartment = row.original.apartment;
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, apartment.full_address),
                h('span', { class: 'text-sm text-muted-foreground' }, `#${apartment.number}`),
            ]);
        },
    }),
    columnHelper.accessor('type_label', {
        header: 'Tipo',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.type_label);
        },
    }),
    columnHelper.accessor('billing_period_label', {
        header: 'Período',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.billing_period_label);
        },
    }),
    columnHelper.accessor('due_date', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Vencimiento', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const dueDate = new Date(row.original.due_date);
            const isOverdue = row.original.status === 'overdue' || (row.original.status === 'pending' && dueDate < new Date());
            return h(
                'div',
                {
                    class: cn('text-sm', isOverdue ? 'font-medium text-red-600' : 'text-muted-foreground'),
                },
                dueDate.toLocaleDateString(),
            );
        },
    }),
    columnHelper.accessor('total_amount', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Total', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const amount = parseFloat(row.original.total_amount || 0);
            return h('div', { class: 'font-medium' }, `$${amount.toLocaleString()}`);
        },
    }),
    columnHelper.accessor('balance_due', {
        header: 'Saldo',
        cell: ({ row }) => {
            const balance = parseFloat(row.original.balance_due || 0);
            const isZero = balance <= 0;
            return h(
                'div',
                {
                    class: cn('font-medium', isZero ? 'text-green-600' : 'text-orange-600'),
                },
                `$${balance.toLocaleString()}`,
            );
        },
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: ({ row }) => {
            const invoice = row.original;
            return h(
                Badge,
                {
                    class: invoice.status_badge.class,
                },
                () => invoice.status_badge.text,
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const invoice = row.original;
            const actions = [
                {
                    label: 'Ver Detalle',
                    icon: Eye,
                    href: `/invoices/${invoice.id}`,
                },
                {
                    label: 'Editar',
                    icon: Edit,
                    href: `/invoices/${invoice.id}/edit`,
                    disabled: ['paid', 'cancelled'].includes(invoice.status),
                },
                {
                    label: 'Eliminar',
                    icon: Trash2,
                    action: () => deleteInvoice(invoice.id),
                    destructive: true,
                    disabled: ['paid', 'partial'].includes(invoice.status),
                },
            ];
            return h(DropdownAction, { actions });
        },
    }),
];

const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    manualPagination: true,
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

// Apply filters
const applyFilters = () => {
    const params: Record<string, string> = {};

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.status !== 'all') params.status = customFilters.value.status;
    if (customFilters.value.type !== 'all') params.type = customFilters.value.type;
    if (customFilters.value.apartment_id !== 'all') params.apartment_id = customFilters.value.apartment_id;
    if (customFilters.value.period) params.period = customFilters.value.period;

    router.get('/invoices', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Navigate to specific page
const goToPage = (page: number) => {
    const params: Record<string, string> = { page: page.toString() };

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.status !== 'all') params.status = customFilters.value.status;
    if (customFilters.value.type !== 'all') params.type = customFilters.value.type;
    if (customFilters.value.apartment_id !== 'all') params.apartment_id = customFilters.value.apartment_id;
    if (customFilters.value.period) params.period = customFilters.value.period;

    router.get('/invoices', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    customFilters.value = {
        search: '',
        status: 'all',
        type: 'all',
        apartment_id: 'all',
        period: '',
    };
    applyFilters();
};

// Delete invoice
const deleteInvoice = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta factura?')) {
        router.delete(`/invoices/${id}`);
    }
};

// Watch for filter changes
watch(
    customFilters,
    () => {
        applyFilters();
    },
    { deep: true },
);

// Status options
const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'partial', label: 'Pago parcial' },
    { value: 'paid', label: 'Pagado' },
    { value: 'overdue', label: 'Vencido' },
    { value: 'cancelled', label: 'Cancelado' },
];

// Type options
const typeOptions = [
    { value: 'all', label: 'Todos los tipos' },
    { value: 'monthly', label: 'Mensual' },
    { value: 'individual', label: 'Individual' },
    { value: 'late_fee', label: 'Intereses' },
];
</script>

<template>
    <Head title="Facturas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Flash Messages -->
            <Alert v-if="flashSuccess" class="mb-4">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flashSuccess }}</AlertDescription>
            </Alert>

            <Alert v-if="flashError" variant="destructive" class="mb-4">
                <XCircle class="h-4 w-4" />
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <!-- Validation Errors -->
            <ValidationErrors :errors="errors" />

            <!-- Filters -->
            <Card class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-4 w-4" />
                        <Label class="text-sm font-medium">Filtros</Label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                <Input id="search" placeholder="Número de factura..." v-model="customFilters.search" class="pl-8" />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <Select v-model="customFilters.status">
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

                        <!-- Type Filter -->
                        <div class="space-y-2">
                            <Label>Tipo</Label>
                            <Select v-model="customFilters.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in typeOptions" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Apartment Filter -->
                        <div class="space-y-2">
                            <Label>Apartamento</Label>
                            <Select v-model="customFilters.apartment_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar apartamento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los apartamentos</SelectItem>
                                    <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                        {{ apartment.full_address }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Period Filter -->
                        <div class="space-y-2">
                            <Label for="period">Período</Label>
                            <Input id="period" type="month" v-model="customFilters.period" />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center space-x-2">
                        <Button @click="clearFilters" variant="outline" size="sm">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar Filtros
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Table -->
            <Card>
                <div class="p-6">
                    <!-- Table controls -->
                    <div class="flex items-center justify-between pb-4">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm text-muted-foreground">
                                Mostrando {{ props.invoices.from || 0 }} a {{ props.invoices.to || 0 }} de {{ props.invoices.total }} facturas
                            </p>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    Columnas
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuCheckboxItem
                                    v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                    :key="column.id"
                                    :checked="column.getIsVisible()"
                                    @update:checked="column.toggleVisibility"
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
                                        @click="router.visit(`/invoices/${row.original.id}`)"
                                    >
                                        <TableCell
                                            v-for="cell in row.getVisibleCells()"
                                            :key="cell.id"
                                            :class="cn(cell.column.id === 'select' || cell.column.id === 'actions' ? 'cursor-default' : '')"
                                            @click="cell.column.id === 'select' || cell.column.id === 'actions' ? $event.stopPropagation() : null"
                                        >
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell :colSpan="columns.length" class="h-24 text-center"> No se encontraron facturas. </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm text-muted-foreground">
                                {{ table.getFilteredSelectedRowModel().rows.length }} de {{ props.invoices.data.length }} fila(s) seleccionadas.
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.invoices.current_page <= 1"
                                @click="goToPage(props.invoices.current_page - 1)"
                            >
                                Anterior
                            </Button>

                            <span class="px-2 text-sm text-muted-foreground">
                                Página {{ props.invoices.current_page }} de {{ props.invoices.last_page }}
                            </span>

                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.invoices.current_page >= props.invoices.last_page"
                                @click="goToPage(props.invoices.current_page + 1)"
                            >
                                Siguiente
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 py-4">
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
                            :checked="column.getIsVisible()"
                            @update:checked="(value) => column.toggleVisibility(!!value)"
                        >
                            {{
                                column.id === 'invoice_number'
                                    ? 'Número'
                                    : column.id === 'apartment'
                                      ? 'Apartamento'
                                      : column.id === 'type'
                                        ? 'Tipo'
                                        : column.id === 'billing_period_label'
                                          ? 'Período'
                                          : column.id === 'total_amount'
                                            ? 'Total'
                                            : column.id === 'balance_due'
                                              ? 'Saldo'
                                              : column.id === 'status'
                                                ? 'Estado'
                                                : column.id
                            }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button asChild variant="outline">
                    <Link href="/payments">
                        <Receipt class="mr-2 h-4 w-4" />
                        Volver a Pagos
                    </Link>
                </Button>

                <Button asChild>
                    <Link href="/invoices/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Factura
                    </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
