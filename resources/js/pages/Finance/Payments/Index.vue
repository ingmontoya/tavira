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

export interface Payment {
    id: number;
    payment_number: string;
    apartment: {
        id: number;
        number: string;
    };
    total_amount: number | string;
    applied_amount: number | string;
    remaining_amount: number | string;
    payment_date: string;
    payment_method: string;
    payment_method_label: string;
    reference_number?: string;
    status: 'pending' | 'applied' | 'partially_applied' | 'reversed';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    created_by: {
        id: number;
        name: string;
    };
    applications: {
        id: number;
        invoice: {
            invoice_number: string;
        };
        amount_applied: number;
    }[];
    created_at: string;
}

const props = defineProps<{
    payments: {
        data: Payment[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    apartments: Array<{
        id: number;
        number: string;
    }>;
    filters?: {
        search?: string;
        status?: string;
        apartment_id?: string;
        start_date?: string;
        end_date?: string;
    };
    statuses: Record<string, string>;
    paymentMethods: Record<string, string>;
}>();

const data: Payment[] = props.payments.data;

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    apartment_id: props.filters?.apartment_id || 'all',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
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
        apartment_id: 'all',
        start_date: '',
        end_date: '',
    };
    // Also clear table filters
    table.getColumn('payment_number')?.setFilterValue('');
};

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (payment) =>
                payment.payment_number?.toLowerCase().includes(searchTerm) ||
                payment.reference_number?.toLowerCase().includes(searchTerm) ||
                payment.apartment?.number?.toLowerCase().includes(searchTerm) ||
                payment.created_by?.name?.toLowerCase().includes(searchTerm),
        );
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        filtered = filtered.filter((payment) => payment.status === customFilters.value.status);
    }

    // Apartment filter
    if (customFilters.value.apartment_id !== 'all') {
        filtered = filtered.filter((payment) => payment.apartment.id.toString() === customFilters.value.apartment_id);
    }

    // Date range filter
    if (customFilters.value.start_date) {
        filtered = filtered.filter((payment) => payment.payment_date >= customFilters.value.start_date);
    }
    if (customFilters.value.end_date) {
        filtered = filtered.filter((payment) => payment.payment_date <= customFilters.value.end_date);
    }

    return filtered;
});

const columnHelper = createColumnHelper<Payment>();

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
    columnHelper.accessor('payment_number', {
        enablePinning: true,
        header: 'Número de Pago',
        cell: ({ row }) => {
            const payment = row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-mono text-sm font-medium' }, payment.payment_number),
                h('div', { class: 'text-xs text-muted-foreground' }, payment.payment_method_label),
            ]);
        },
    }),
    columnHelper.accessor('apartment', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const apartment = row.getValue('apartment') as Payment['apartment'];
            return h('div', { class: 'font-medium' }, apartment.number);
        },
    }),
    columnHelper.accessor('payment_date', {
        enablePinning: true,
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Fecha', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const date = new Date(row.getValue('payment_date'));
            return h('div', { class: 'text-sm' }, date.toLocaleDateString('es-CO'));
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
                () => ['Monto Total', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const amount = parseFloat((row.getValue('total_amount') as string) || '0');
            return h(
                'div',
                {
                    class: 'text-right font-mono text-sm font-medium',
                },
                `$${amount.toLocaleString()}`,
            );
        },
    }),
    columnHelper.accessor('applied_amount', {
        header: 'Monto Aplicado',
        cell: ({ row }) => {
            const appliedAmount = parseFloat((row.getValue('applied_amount') as string) || '0');
            const totalAmount = parseFloat((row.original.total_amount as string) || '0');
            const percentage = totalAmount > 0 ? (appliedAmount / totalAmount) * 100 : 0;

            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'text-right font-mono text-sm' }, `$${appliedAmount.toLocaleString()}`),
                h('div', { class: 'text-xs text-muted-foreground text-right' }, `${percentage.toFixed(0)}%`),
            ]);
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const payment = row.original;
            return h(
                'span',
                {
                    class: `inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${payment.status_badge.class}`,
                },
                payment.status_badge.text,
            );
        },
    }),
    columnHelper.display({
        id: 'applications',
        header: 'Aplicaciones',
        cell: ({ row }) => {
            const payment = row.original;
            return h('div', { class: 'text-sm' }, [
                h('div', { class: 'font-medium' }, `${payment.applications.length} factura(s)`),
                payment.applications.length > 0 &&
                    h('div', { class: 'text-xs text-muted-foreground' }, payment.applications.map((app) => app.invoice.invoice_number).join(', ')),
            ]);
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const payment = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    payment: payment,
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
        title: 'Finanzas',
        href: '/finances',
    },
    {
        title: 'Pagos',
        href: '/finance/payments',
    },
];

const exportPayments = () => {
    window.location.href = '/finance/payments/export';
};
</script>

<template>
    <Head title="Gestión de Pagos" />

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
                                placeholder="Buscar por número de pago, referencia, apartamento o usuario..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem v-for="(label, value) in statuses" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_apartment">Apartamento</Label>
                            <Select v-model="customFilters.apartment_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los apartamentos" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los apartamentos</SelectItem>
                                    <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                        {{ apartment.number }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_date_from">Fecha Desde</Label>
                            <Input id="filter_date_from" v-model="customFilters.start_date" type="date" class="w-full" />
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_date_to">Fecha Hasta</Label>
                            <Input id="filter_date_to" v-model="customFilters.end_date" type="date" class="w-full" />
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                        <div class="text-sm text-muted-foreground">Mostrando {{ filteredData.length }} de {{ data.length }} pagos</div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por número de pago..."
                    :model-value="table.getColumn('payment_number')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('payment_number')?.setFilterValue($event)"
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

                <Button @click="router.visit('/finance/payments/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Pago
                </Button>

                <Button variant="outline" @click="exportPayments">
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
                                    @click="router.visit(`/finance/payments/${row.original.id}`)"
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
