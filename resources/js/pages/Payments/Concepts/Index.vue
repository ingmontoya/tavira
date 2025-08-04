<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
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
import { ChevronDown, ChevronsUpDown, Edit, Eye, Filter, Plus, Search, Settings, ToggleLeft, ToggleRight, Trash2, X } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { valueUpdater } from '../../../utils';

interface PaymentConcept {
    id: number;
    name: string;
    description?: string;
    type: string;
    type_label: string;
    default_amount: number;
    is_recurring: boolean;
    is_active: boolean;
    billing_cycle: string;
    billing_cycle_label: string;
    applicable_apartment_types?: number[];
}

const props = defineProps<{
    concepts: {
        data: PaymentConcept[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters?: {
        search?: string;
        type?: string;
        is_active?: boolean;
    };
}>();

const data = computed(() => props.concepts.data);

// Custom filters state
const customFilters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || 'all',
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
                type: newFilters.type || 'all',
                is_active: newFilters.is_active !== undefined ? newFilters.is_active.toString() : 'all',
            };
        } else {
            // No filters from server, reset to defaults
            newCustomFilters = {
                search: '',
                type: 'all',
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
const columnHelper = createColumnHelper<PaymentConcept>();

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
    columnHelper.accessor('name', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Nombre', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const concept = row.original;
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, concept.name),
                concept.description &&
                    h(
                        'span',
                        { class: 'text-sm text-muted-foreground' },
                        concept.description.substring(0, 60) + (concept.description.length > 60 ? '...' : ''),
                    ),
            ]);
        },
    }),
    columnHelper.accessor('type_label', {
        header: 'Tipo',
        cell: ({ row }) => {
            const concept = row.original;
            const typeColors = {
                common_expense: 'bg-blue-100 text-blue-800',
                sanction: 'bg-red-100 text-red-800',
                parking: 'bg-green-100 text-green-800',
                special: 'bg-purple-100 text-purple-800',
                late_fee: 'bg-orange-100 text-orange-800',
                other: 'bg-gray-100 text-gray-800',
            };
            return h(
                Badge,
                {
                    class: typeColors[concept.type as keyof typeof typeColors] || 'bg-gray-100 text-gray-800',
                },
                () => concept.type_label,
            );
        },
    }),
    columnHelper.accessor('default_amount', {
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Monto', h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const amount = row.original.default_amount;
            return h('div', { class: 'font-medium' }, `$${amount.toLocaleString()}`);
        },
    }),
    columnHelper.accessor('billing_cycle_label', {
        header: 'Ciclo',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.billing_cycle_label);
        },
    }),
    columnHelper.accessor('is_recurring', {
        header: 'Recurrente',
        cell: ({ row }) => {
            const isRecurring = row.original.is_recurring;
            return h(
                Badge,
                {
                    variant: isRecurring ? 'default' : 'secondary',
                },
                () => (isRecurring ? 'Sí' : 'No'),
            );
        },
    }),
    columnHelper.accessor('is_active', {
        header: 'Estado',
        cell: ({ row }) => {
            const concept = row.original;
            return h(
                Badge,
                {
                    class: concept.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                },
                () => (concept.is_active ? 'Activo' : 'Inactivo'),
            );
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const concept = row.original;
            const actions = [
                {
                    label: 'Ver Detalle',
                    icon: Eye,
                    href: `/payment-concepts/${concept.id}`,
                },
                {
                    label: 'Editar',
                    icon: Edit,
                    href: `/payment-concepts/${concept.id}/edit`,
                },
                {
                    label: concept.is_active ? 'Desactivar' : 'Activar',
                    icon: concept.is_active ? ToggleLeft : ToggleRight,
                    action: () => toggleConcept(concept.id),
                },
                {
                    label: 'Eliminar',
                    icon: Trash2,
                    action: () => deleteConcept(concept.id),
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
    if (customFilters.value.type && customFilters.value.type !== 'all') {
        filterData.type = customFilters.value.type;
    }
    if (customFilters.value.is_active && customFilters.value.is_active !== 'all') {
        filterData.is_active = customFilters.value.is_active;
    }

    router.get('/payment-concepts', filterData, {
        preserveState: true,
        preserveScroll: true,
        only: ['concepts', 'filters'],
    });
};

// Check if custom filters are active
const hasActiveCustomFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== '' && value !== 'all');
});

// Clear custom filters
const clearCustomFilters = () => {
    console.log('🔄 clearCustomFilters called');
    console.log('📋 Current customFilters:', JSON.stringify(customFilters.value));
    console.log('📋 Current props.filters:', JSON.stringify(props.filters));
    console.log('📋 Current concepts count:', props.concepts.data.length);

    router.get(
        '/payment-concepts',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['concepts', 'filters'],
            onSuccess: (page) => {
                console.log('✅ Request successful');
                console.log('📋 New page.props.filters:', JSON.stringify(page.props.filters));
                console.log('📋 New concepts count:', page.props.concepts.data.length);
            },
            onError: (errors) => {
                console.log('❌ Request failed:', errors);
            },
            onFinish: () => {
                console.log('🏁 Request finished');
            },
        },
    );
};

// Toggle concept status
const toggleConcept = (id: number) => {
    router.post(
        `/payment-concepts/${id}/toggle`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Delete concept
const deleteConcept = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este concepto de pago?')) {
        router.delete(`/payment-concepts/${id}`);
    }
};

// Manual filter application

// Pagination functions
const nextPage = () => {
    const currentPage = props.concepts.current_page;
    if (currentPage < props.concepts.last_page) {
        const filterData = { ...customFilters.value, page: (currentPage + 1).toString() };
        // Clean up 'all' values
        Object.keys(filterData).forEach((key) => {
            if (filterData[key] === 'all' || filterData[key] === '') {
                delete filterData[key];
            }
        });
        router.get('/payment-concepts', filterData, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const previousPage = () => {
    const currentPage = props.concepts.current_page;
    if (currentPage > 1) {
        const filterData = { ...customFilters.value, page: (currentPage - 1).toString() };
        // Clean up 'all' values
        Object.keys(filterData).forEach((key) => {
            if (filterData[key] === 'all' || filterData[key] === '') {
                delete filterData[key];
            }
        });
        router.get('/payment-concepts', filterData, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Type options
const typeOptions = [
    { value: 'all', label: 'Todos los tipos' },
    { value: 'common_expense', label: 'Administración' },
    { value: 'sanction', label: 'Sanción' },
    { value: 'parking', label: 'Parqueadero' },
    { value: 'special', label: 'Especial' },
    { value: 'late_fee', label: 'Interés de mora' },
    { value: 'other', label: 'Otro' },
];

// Status options
const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'true', label: 'Activos' },
    { value: 'false', label: 'Inactivos' },
];
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Conceptos de Pago',
        href: '/payment-concepts',
    },
];
</script>

<template>
    <Head title="Conceptos de Pago" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Conceptos de Pago</h1>
                    <p class="text-muted-foreground">Configura los conceptos que se pueden facturar en el conjunto</p>
                </div>
                <div class="flex space-x-2">
                    <Button asChild variant="outline">
                        <Link href="/payments">
                            <Settings class="mr-2 h-4 w-4" />
                            Volver a Pagos
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href="/payment-concepts/create">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Concepto
                        </Link>
                    </Button>
                </div>
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
                                    placeholder="Nombre o descripción..."
                                    v-model="customFilters.search"
                                    class="pl-8"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
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
                            Mostrando {{ props.concepts.from || 0 }} - {{ props.concepts.to || 0 }} de {{ props.concepts.total || 0 }} conceptos
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
                                        @click="router.visit(`/payment-concepts/${row.original.id}`)"
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
                                            No se encontraron conceptos de pago.
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between space-x-2 pt-4">
                        <div class="flex-1 text-sm text-muted-foreground">
                            {{ table.getFilteredSelectedRowModel().rows.length }} de {{ props.concepts.total }} fila(s) seleccionadas.
                        </div>
                        <div class="flex items-center space-x-6 lg:space-x-8">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium">Página</p>
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium">{{ props.concepts.current_page }}</span>
                                    <span class="text-sm text-muted-foreground">de {{ props.concepts.last_page }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Button variant="outline" size="sm" :disabled="props.concepts.current_page <= 1" @click="previousPage">
                                    Anterior
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="props.concepts.current_page >= props.concepts.last_page"
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
