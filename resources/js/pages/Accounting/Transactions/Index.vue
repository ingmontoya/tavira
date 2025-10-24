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
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, Download, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { cn, valueUpdater } from '../../../utils';

export interface AccountingTransaction {
    id: number;
    reference: string;
    description: string;
    transaction_date: string;
    total_amount: number;
    status: 'borrador' | 'contabilizado' | 'cancelado';
    apartment_number?: string;
    created_by: {
        id: number;
        name: string;
    };
    entries: {
        id: number;
        account: {
            code: string;
            name: string;
        };
        debit_amount: number;
        credit_amount: number;
    }[];
    created_at: string;
}

const props = defineProps<{
    transactions: {
        data: AccountingTransaction[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters?: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const data: AccountingTransaction[] = props.transactions.data;

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '');
});

// Clear custom filters and reload from server
const clearCustomFilters = () => {
    router.get('/accounting/transactions', {}, { preserveState: true });
};

// Apply filters by making a server request
const applyFilters = () => {
    const params: any = {};

    if (customFilters.value.search) {
        params.search = customFilters.value.search;
    }
    if (customFilters.value.status) {
        params.status = customFilters.value.status;
    }
    if (customFilters.value.start_date) {
        params.start_date = customFilters.value.start_date;
    }
    if (customFilters.value.end_date) {
        params.end_date = customFilters.value.end_date;
    }

    router.get('/accounting/transactions', params, { preserveState: true, preserveScroll: true });
};

const columnHelper = createColumnHelper<AccountingTransaction>();

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
    columnHelper.display({
        id: 'apartment',
        enablePinning: true,
        header: 'Apartamento',
        cell: ({ row }) => {
            const transaction = row.original;

            // Extract apartment number from description or reference
            let apartmentNumber = transaction.apartment_number;
            if (!apartmentNumber && transaction.description) {
                const match = transaction.description.match(/Apto\s+([A-Z0-9]+)/);
                apartmentNumber = match ? match[1] : null;
            }

            if (apartmentNumber) {
                return h('div', { class: 'font-mono text-sm font-medium' }, apartmentNumber);
            }

            return h('div', { class: 'text-muted-foreground text-sm' }, '-');
        },
    }),
    columnHelper.accessor('reference', {
        enablePinning: true,
        header: 'Referencia',
        cell: ({ row }) => {
            const reference = row.getValue('reference') as any;
            const referenceType = row.original.reference_type;

            if (!reference || !referenceType) {
                return h('div', { class: 'text-muted-foreground text-sm' }, 'Manual');
            }

            let displayText = '';
            if (referenceType === 'invoice' && reference.invoice_number) {
                displayText = reference.invoice_number;
            } else if (referenceType === 'payment' && reference.payment_number) {
                displayText = reference.payment_number;
            } else {
                displayText = `${referenceType.charAt(0).toUpperCase() + referenceType.slice(1)} #${reference.id}`;
            }

            return h('div', { class: 'font-mono text-sm' }, displayText);
        },
    }),
    columnHelper.accessor('transaction_date', {
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
            const date = new Date(row.getValue('transaction_date'));
            return h('div', { class: 'text-sm' }, date.toLocaleDateString('es-CO'));
        },
    }),
    columnHelper.accessor('description', {
        header: 'Descripción',
        cell: ({ row }) => {
            const transaction = row.original;
            return h('div', { class: 'space-y-1 max-w-xs' }, [
                h('div', { class: 'font-medium truncate' }, transaction.description),
                h('div', { class: 'text-xs text-muted-foreground' }, `${transaction.entries.length} asiento(s)`),
            ]);
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
            const amount = row.getValue('total_amount') as number;
            const formattedAmount = amount != null ? amount.toLocaleString() : '0';
            return h(
                'div',
                {
                    class: 'text-right font-mono text-sm font-medium',
                },
                `$${formattedAmount}`,
            );
        },
    }),
    columnHelper.accessor('status', {
        enablePinning: true,
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const statusMap = {
                borrador: { label: 'Borrador', color: 'bg-yellow-100 text-yellow-800' },
                contabilizado: { label: 'Contabilizada', color: 'bg-green-100 text-green-800' },
                cancelado: { label: 'Cancelada', color: 'bg-red-100 text-red-800' },
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
        id: 'created_by',
        header: 'Creado por',
        cell: ({ row }) => {
            const transaction = row.original;
            return h('div', { class: 'text-sm' }, transaction.created_by.name);
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: any }) => {
            const transaction = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    transaction: transaction,
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
            left: ['apartment', 'status'],
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
        title: 'Transacciones',
        href: '/accounting/transactions',
    },
];

const exportTransactions = () => {
    window.location.href = '/accounting/transactions/export';
};
</script>

<template>
    <Head title="Transacciones Contables" />

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
                                placeholder="Buscar por apartamento, referencia, descripción, cuenta o usuario..."
                                class="max-w-md pl-10"
                            />
                        </div>
                    </div>

                    <!-- Filtros por categorías -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="min-w-0 space-y-2">
                            <Label for="filter_status">Estado</Label>
                            <Select v-model="customFilters.status" @update:model-value="applyFilters">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Todos los estados" class="truncate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos los estados</SelectItem>
                                    <SelectItem value="borrador">Borrador</SelectItem>
                                    <SelectItem value="posted">Contabilizada</SelectItem>
                                    <SelectItem value="cancelled">Cancelada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_date_from">Fecha Desde</Label>
                            <Input id="filter_date_from" v-model="customFilters.start_date" type="date" class="w-full" @change="applyFilters" />
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="filter_date_to">Fecha Hasta</Label>
                            <Input id="filter_date_to" v-model="customFilters.end_date" type="date" class="w-full" @change="applyFilters" />
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            <Button variant="default" @click="applyFilters">
                                <Search class="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                            <Button variant="outline" @click="clearCustomFilters" v-if="hasActiveCustomFilters">
                                <X class="mr-2 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ transactions.from || 0 }} - {{ transactions.to || 0 }} de {{ transactions.total || 0 }} transacciones
                        </div>
                    </div>
                </div>
            </Card>

            <div class="flex items-center gap-2 py-4">
                <Input
                    class="max-w-sm"
                    placeholder="Filtro adicional por referencia..."
                    :model-value="table.getColumn('reference')?.getFilterValue() as string"
                    @update:model-value="table.getColumn('reference')?.setFilterValue($event)"
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

                <Button @click="router.visit('/accounting/transactions/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Transacción
                </Button>

                <Button variant="outline" @click="exportTransactions">
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
                                    @click="router.visit(`/accounting/transactions/${row.original.id}`)"
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
                <div class="flex items-center space-x-2">
                    <div class="text-sm text-muted-foreground">
                        {{ table.getFilteredSelectedRowModel().rows.length }} de {{ table.getFilteredRowModel().rows.length }} fila(s) seleccionadas.
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="text-sm text-muted-foreground">
                        Mostrando {{ transactions.from || 0 }} - {{ transactions.to || 0 }} de {{ transactions.total || 0 }} transacciones
                    </div>
                    <div class="space-x-2">
                        <Button variant="outline" size="sm" :disabled="!transactions.prev_page_url" @click="router.visit(transactions.prev_page_url)">
                            Anterior
                        </Button>
                        <Button variant="outline" size="sm" :disabled="!transactions.next_page_url" @click="router.visit(transactions.next_page_url)">
                            Siguiente
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
