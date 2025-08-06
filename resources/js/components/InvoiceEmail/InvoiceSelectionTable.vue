<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ChevronsUpDown, Eye } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { cn, valueUpdater } from '@/utils';
import type { Invoice } from '@/types';

interface Props {
    invoices: Invoice[];
    selectedInvoiceIds: number[];
    showActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
});

// Emit events
const emit = defineEmits<{
    'selection-change': [selectedIds: number[]];
}>();

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref<Record<string, boolean>>({});

// Initialize selection state based on props
const initializeSelection = () => {
    const selection: Record<string, boolean> = {};
    props.invoices.forEach((invoice, index) => {
        if (props.selectedInvoiceIds.includes(invoice.id)) {
            selection[index.toString()] = true;
        }
    });
    rowSelection.value = selection;
};

// Watch for changes in selectedInvoiceIds prop
watch(() => props.selectedInvoiceIds, () => {
    initializeSelection();
}, { immediate: true });

// Column helper
const columnHelper = createColumnHelper<Invoice>();

const columns = [
    columnHelper.display({
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                checked: table.getIsAllPageRowsSelected(),
                indeterminate: table.getIsSomePageRowsSelected(),
                'onUpdate:checked': (value) => {
                    table.toggleAllPageRowsSelected(!!value);
                    updateSelection();
                },
                ariaLabel: 'Seleccionar todas',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                checked: row.getIsSelected(),
                'onUpdate:checked': (value) => {
                    row.toggleSelected(!!value);
                    updateSelection();
                },
                ariaLabel: 'Seleccionar fila',
            }),
        enableSorting: false,
        enableHiding: false,
        size: 50,
    }),
    columnHelper.accessor('invoice_number', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    class: 'h-auto p-0 font-medium',
                },
                () => ['Número', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const invoice = row.original;
            return h('div', { class: 'font-medium' }, invoice.invoice_number);
        },
        size: 120,
    }),
    columnHelper.accessor('apartment.full_address', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const apartment = row.original.apartment;
            if (!apartment) {
                return h('div', { class: 'flex flex-col' }, [
                    h('span', { class: 'font-medium text-sm text-red-600' }, 'Sin apartamento'),
                    h('span', { class: 'text-xs text-muted-foreground' }, 'N/A'),
                ]);
            }
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium text-sm' }, apartment.full_address || 'Dirección no disponible'),
                h('span', { class: 'text-xs text-muted-foreground' }, `#${apartment.number || 'N/A'}`),
            ]);
        },
        size: 150,
    }),
    columnHelper.accessor('type_label', {
        header: 'Tipo',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.type_label);
        },
        size: 100,
    }),
    columnHelper.accessor('billing_period_label', {
        header: 'Período',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.billing_period_label);
        },
        size: 120,
    }),
    columnHelper.accessor('due_date', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    class: 'h-auto p-0 font-medium',
                },
                () => ['Vencimiento', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const dueDate = new Date(row.original.due_date);
            const isOverdue = row.original.status === 'overdue' || 
                            (row.original.status === 'pending' && dueDate < new Date());
            return h(
                'div',
                {
                    class: cn('text-sm', isOverdue ? 'font-medium text-red-600' : 'text-muted-foreground'),
                },
                dueDate.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit',
                }),
            );
        },
        size: 100,
    }),
    columnHelper.accessor('balance_due', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    class: 'h-auto p-0 font-medium',
                },
                () => ['Saldo', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const balance = parseFloat(row.original.balance_due.toString());
            return h(
                'div',
                {
                    class: cn('font-medium text-sm', balance <= 0 ? 'text-green-600' : 'text-orange-600'),
                },
                `$${balance.toLocaleString()}`,
            );
        },
        size: 100,
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: ({ row }) => {
            const invoice = row.original;
            return h(
                Badge,
                {
                    class: cn(invoice.status_badge.class, 'text-xs'),
                    variant: 'outline',
                },
                () => invoice.status_badge.text,
            );
        },
        size: 100,
    }),
];

// Add actions column if enabled
if (props.showActions) {
    columns.push(
        columnHelper.display({
            id: 'actions',
            header: 'Acciones',
            cell: ({ row }) => {
                const invoice = row.original;
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'sm',
                        onClick: (event: Event) => {
                            event.stopPropagation();
                            router.visit(`/invoices/${invoice.id}`);
                        },
                        class: 'h-8 w-8 p-0',
                    },
                    () => [h(Eye, { class: 'h-4 w-4' })],
                );
            },
            size: 60,
        })
    );
}

// Vue table setup
const table = useVueTable({
    get data() {
        return props.invoices;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => {
        valueUpdater(updaterOrValue, rowSelection);
        updateSelection();
    },
    state: {
        sorting: sorting.value,
        columnFilters: columnFilters.value,
        columnVisibility: columnVisibility.value,
        rowSelection: rowSelection.value,
    },
});

// Update selection and emit to parent
const updateSelection = () => {
    const selectedRows = table.getFilteredSelectedRowModel().rows;
    const selectedIds = selectedRows.map((row) => row.original.id);
    emit('selection-change', selectedIds);
};

// Computed values
const selectedCount = computed(() => {
    return table.getFilteredSelectedRowModel().rows.length;
});

const totalAmount = computed(() => {
    const selectedRows = table.getFilteredSelectedRowModel().rows;
    return selectedRows.reduce((sum, row) => {
        return sum + parseFloat(row.original.balance_due.toString());
    }, 0);
});

// Initialize selection on mount
initializeSelection();
</script>

<template>
    <div class="space-y-4">
        <!-- Selection Summary -->
        <div v-if="selectedCount > 0" class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center space-x-4">
                <div class="text-sm font-medium text-blue-900">
                    {{ selectedCount }} facturas seleccionadas
                </div>
                <div class="text-sm text-blue-700">
                    Total: ${{ totalAmount.toLocaleString() }}
                </div>
            </div>
            <Button 
                variant="outline" 
                size="sm"
                @click="table.resetRowSelection()"
                class="text-blue-700 border-blue-300 hover:bg-blue-100"
            >
                Deseleccionar todas
            </Button>
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead 
                            v-for="header in headerGroup.headers" 
                            :key="header.id"
                            :style="{ width: header.getSize() + 'px' }"
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
                            :data-state="row.getIsSelected() ? 'selected' : undefined"
                            class="cursor-pointer transition-colors hover:bg-muted/50"
                            @click="row.toggleSelected()"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                :class="cn(
                                    'py-2',
                                    cell.column.id === 'select' || cell.column.id === 'actions' 
                                        ? 'cursor-default' : ''
                                )"
                                @click="cell.column.id === 'select' || cell.column.id === 'actions' 
                                    ? $event.stopPropagation() : null"
                            >
                                <FlexRender 
                                    :render="cell.column.columnDef.cell" 
                                    :props="cell.getContext()" 
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colSpan="columns.length" class="h-24 text-center">
                                No se encontraron facturas elegibles.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Table Summary -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <div>
                Mostrando {{ table.getRowModel().rows.length }} facturas
            </div>
            <div v-if="selectedCount > 0">
                {{ selectedCount }} de {{ table.getRowModel().rows.length }} seleccionadas
            </div>
        </div>
    </div>
</template>