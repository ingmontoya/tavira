<template>
    <Head title="Gestión de Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Acuerdos de Pago</h1>
                    <p class="mt-1 text-sm text-gray-600">Gestiona los acuerdos de pago para propietarios morosos</p>
                </div>
                <Link
                    :href="route('payment-agreements.create')"
                    class="inline-flex items-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Acuerdo
                </Link>
            </div>

            <!-- Status Filter Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="(count, status) in statusCounts"
                        :key="status"
                        @click="filterByStatus(status)"
                        :class="[
                            customFilters.status === status || (status === 'all' && (!customFilters.status || customFilters.status === 'all'))
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        {{ getStatusLabel(status) }}
                        <span class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-900">
                            {{ count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="max-w-lg flex-1">
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <Input v-model="customFilters.search" @input="applyFilters" placeholder="Buscar por apartamento..." class="pl-10" />
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <Select v-model="customFilters.status" @update:model-value="applyFilters">
                        <SelectTrigger class="w-48">
                            <SelectValue placeholder="Todos los estados" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estados</SelectItem>
                            <SelectItem value="draft">Borrador</SelectItem>
                            <SelectItem value="pending_approval">Pendiente aprobación</SelectItem>
                            <SelectItem value="approved">Aprobado</SelectItem>
                            <SelectItem value="active">Activo</SelectItem>
                            <SelectItem value="breached">Incumplido</SelectItem>
                            <SelectItem value="completed">Completado</SelectItem>
                            <SelectItem value="cancelled">Cancelado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button v-if="hasActiveCustomFilters" @click="clearCustomFilters" variant="outline" size="sm">
                        <X class="mr-1 h-4 w-4" />
                        Limpiar
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                                :data-state="row.getIsSelected() && 'selected'"
                                class="cursor-pointer hover:bg-muted/50"
                                @click="() => router.visit(route('payment-agreements.show', row.original.id))"
                            >
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow>
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <FileText class="mb-2 h-12 w-12 text-gray-400" />
                                        <h3 class="text-sm font-medium text-gray-900">No hay acuerdos de pago</h3>
                                        <p class="text-sm text-gray-500">Comienza creando un nuevo acuerdo de pago para un propietario moroso.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between px-2">
                <div class="flex-1 text-sm text-muted-foreground">{{ filteredData.length }} acuerdo(s) encontrado(s).</div>
                <div class="flex items-center space-x-6 lg:space-x-8">
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Filas por página</p>
                        <Select :model-value="`${table.getState().pagination.pageSize}`" @update:model-value="table.setPageSize">
                            <SelectTrigger class="h-8 w-[70px]">
                                <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
                            </SelectTrigger>
                            <SelectContent side="top">
                                <SelectItem v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="`${pageSize}`">
                                    {{ pageSize }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex w-[100px] items-center justify-center text-sm font-medium">
                        Página {{ table.getState().pagination.pageIndex + 1 }} de {{ table.getPageCount() }}
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            class="hidden h-8 w-8 p-0 lg:flex"
                            :disabled="!table.getCanPreviousPage()"
                            @click="table.setPageIndex(0)"
                        >
                            <span class="sr-only">Ir a la primera página</span>
                            <ChevronDown class="h-4 w-4 rotate-90" />
                        </Button>
                        <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">
                            <span class="sr-only">Ir a la página anterior</span>
                            <ChevronDown class="h-4 w-4 rotate-90" />
                        </Button>
                        <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                            <span class="sr-only">Ir a la página siguiente</span>
                            <ChevronDown class="h-4 w-4 -rotate-90" />
                        </Button>
                        <Button
                            variant="outline"
                            class="hidden h-8 w-8 p-0 lg:flex"
                            :disabled="!table.getCanNextPage()"
                            @click="table.setPageIndex(table.getPageCount() - 1)"
                        >
                            <span class="sr-only">Ir a la última página</span>
                            <ChevronDown class="h-4 w-4 -rotate-90" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/utils/format';
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
import { ChevronDown, Edit, Eye, FileText, Plus, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

interface PaymentAgreement {
    id: number;
    agreement_number: string;
    apartment: {
        full_address: string;
        residents?: Array<{ name: string }>;
    };
    total_debt_amount: number;
    installments: number;
    monthly_payment: number;
    progress_percentage: number;
    status: string;
    status_badge: {
        text: string;
        class: string;
    };
    created_at: string;
    created_by: string;
}

const props = defineProps<{
    agreements: {
        data: PaymentAgreement[];
        from: number;
        to: number;
        total: number;
        links: Array<any>;
    };
    statusCounts: Record<string, number>;
    filters: {
        apartment?: string;
        status?: string;
    };
}>();
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Acuerdos de Pagos',
        href: '/payment-agreements',
    },
];

// Custom filters state
const customFilters = ref({
    search: props.filters?.apartment || '',
    status: props.filters?.status || 'all',
});

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Apply custom filters to data
const filteredData = computed(() => {
    let filtered = props.agreements.data;

    // Search filter
    if (customFilters.value.search) {
        const searchTerm = customFilters.value.search.toLowerCase();
        filtered = filtered.filter(
            (agreement) =>
                agreement.apartment.full_address?.toLowerCase().includes(searchTerm) ||
                agreement.agreement_number?.toLowerCase().includes(searchTerm),
        );
    }

    // Status filter
    if (customFilters.value.status !== 'all') {
        filtered = filtered.filter((agreement) => agreement.status === customFilters.value.status);
    }

    return filtered;
});

// Apply filters with server-side filtering
const applyFilters = () => {
    const filterData: Record<string, string> = {};

    if (customFilters.value.search && customFilters.value.search.trim()) {
        filterData.apartment = customFilters.value.search.trim();
    }
    if (customFilters.value.status && customFilters.value.status !== 'all') {
        filterData.status = customFilters.value.status;
    }

    router.visit('/payment-agreements', {
        data: filterData,
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear custom filters
const clearCustomFilters = () => {
    customFilters.value = {
        search: '',
        status: 'all',
    };
    applyFilters();
};

const filterByStatus = (status: string) => {
    customFilters.value.status = status === 'all' ? 'all' : status;
    applyFilters();
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        all: 'Todos',
        draft: 'Borrador',
        pending_approval: 'Pendiente',
        approved: 'Aprobado',
        active: 'Activo',
        breached: 'Incumplido',
        completed: 'Completado',
        cancelled: 'Cancelado',
    };
    return labels[status] || status;
};

// Table column definitions
const columnHelper = createColumnHelper<PaymentAgreement>();

const columns = [
    columnHelper.accessor('agreement_number', {
        header: 'Acuerdo',
        cell: ({ row }) => {
            const agreement = row.original;
            return h('div', [
                h('div', { class: 'text-sm font-medium text-gray-900' }, agreement.agreement_number),
                h('div', { class: 'text-sm text-gray-500' }, `Creado por ${agreement.created_by}`),
            ]);
        },
    }),
    columnHelper.accessor('apartment.full_address', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const agreement = row.original;
            return h('div', [
                h('div', { class: 'text-sm font-medium text-gray-900' }, agreement.apartment.full_address),
                agreement.apartment.residents?.length
                    ? h('div', { class: 'text-sm text-gray-500' }, agreement.apartment.residents.map((r) => r.name).join(', '))
                    : null,
            ]);
        },
    }),
    columnHelper.accessor('total_debt_amount', {
        header: 'Monto Total',
        cell: ({ row }) => h('div', { class: 'text-sm text-gray-900' }, formatCurrency(row.original.total_debt_amount)),
    }),
    columnHelper.accessor('installments', {
        header: 'Cuotas',
        cell: ({ row }) => {
            const agreement = row.original;
            return h('div', [
                h('div', { class: 'text-sm text-gray-900' }, `${agreement.installments} cuotas`),
                h('div', { class: 'text-xs text-gray-500' }, `${formatCurrency(agreement.monthly_payment)}/mes`),
            ]);
        },
    }),
    columnHelper.accessor('progress_percentage', {
        header: 'Progreso',
        cell: ({ row }) => {
            const percentage = row.original.progress_percentage;
            return h('div', { class: 'flex items-center' }, [
                h('div', { class: 'flex-1 bg-gray-200 rounded-full h-2 mr-3' }, [
                    h('div', {
                        class: 'bg-blue-600 h-2 rounded-full',
                        style: { width: `${percentage}%` },
                    }),
                ]),
                h('span', { class: 'text-sm text-gray-900 min-w-0' }, `${percentage}%`),
            ]);
        },
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: ({ row }) => {
            const badge = row.original.status_badge;
            return h(
                'span',
                {
                    class: `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.class}`,
                },
                badge.text,
            );
        },
    }),
    columnHelper.accessor('created_at', {
        header: 'Fecha',
        cell: ({ row }) => h('div', { class: 'text-sm text-gray-500' }, formatDate(row.original.created_at)),
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const agreement = row.original;
            return h(
                'div',
                {
                    class: 'flex items-center justify-end space-x-2',
                    onClick: (e: Event) => e.stopPropagation(), // Prevent row click
                },
                [
                    h(
                        Link,
                        {
                            href: route('payment-agreements.show', agreement.id),
                            class: 'text-blue-600 hover:text-blue-900 p-1',
                            title: `Ver acuerdo ${agreement.agreement_number}`,
                        },
                        () => h(Eye, { class: 'w-4 h-4' }),
                    ),
                    ['draft', 'pending_approval'].includes(agreement.status)
                        ? h(
                              Link,
                              {
                                  href: route('payment-agreements.edit', agreement.id),
                                  class: 'text-indigo-600 hover:text-indigo-900 p-1',
                                  title: `Editar acuerdo ${agreement.agreement_number}`,
                              },
                              () => h(Edit, { class: 'w-4 h-4' }),
                          )
                        : null,
                ],
            );
        },
    }),
];

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});

// Table instance
const table = useVueTable({
    get data() {
        return filteredData.value;
    },
    columns,
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onColumnFiltersChange: (updater) => {
        columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater;
    },
    onColumnVisibilityChange: (updater) => {
        columnVisibility.value = typeof updater === 'function' ? updater(columnVisibility.value) : updater;
    },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
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
    initialState: {
        pagination: {
            pageSize: 15,
        },
    },
});
</script>
