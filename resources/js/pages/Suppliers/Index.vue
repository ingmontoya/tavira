<script setup lang="ts">
import DropdownAction from '@/components/DataTableDropDown.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { CheckCircle, ChevronDown, ChevronsUpDown, Copy, Edit, Eye, Filter, Plus, Search, Trash2, X, XCircle, Building2, Clock } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { valueUpdater } from '../../utils';
import { formatCurrency } from '@/utils/format';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/suppliers',
    },
];

interface Supplier {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    country?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
    notes?: string;
    tax_regime?: string;
    is_active: boolean;
    status_badge: {
        text: string;
        class: string;
    };
    full_contact_info: string;
    expenses_count: number;
    total_expenses: number;
    last_expense_date?: string;
    created_at: string;
}

interface Stats {
    total_suppliers: number;
    active_suppliers: number;
    inactive_suppliers: number;
    total_expenses: number;
}

const props = defineProps<{
    suppliers: {
        data: Supplier[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: Stats;
    filters?: {
        search?: string;
        status?: string;
        document_type?: string;
        city?: string;
    };
}>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const data = computed(() => props.suppliers?.data || []);

// Search and filters
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const documentTypeFilter = ref(props.filters?.document_type || 'all');
const cityFilter = ref(props.filters?.city || '');

// Table setup
const columnHelper = createColumnHelper<Supplier>();

const columns = [
    columnHelper.accessor('name', {
        header: 'Nombre',
        cell: info => {
            const supplier = info.row.original;
            return h('div', { class: 'space-y-1' }, [
                h('div', { class: 'font-medium' }, supplier.name),
                h('div', { class: 'text-xs text-muted-foreground' },
                    `${supplier.document_type}: ${supplier.document_number}`
                )
            ]);
        },
    }),
    columnHelper.accessor('full_contact_info', {
        header: 'Contacto',
        cell: info => {
            const supplier = info.row.original;
            return h('div', { class: 'space-y-1' }, [
                supplier.email ? h('div', { class: 'text-sm' }, supplier.email) : null,
                supplier.phone ? h('div', { class: 'text-xs text-muted-foreground' }, supplier.phone) : null,
                (!supplier.email && !supplier.phone) ? h('div', { class: 'text-xs text-muted-foreground' }, 'Sin contacto') : null,
            ].filter(Boolean));
        },
    }),
    columnHelper.accessor('city', {
        header: 'Ciudad',
        cell: info => {
            const value = info.getValue();
            return value || '-';
        },
    }),
    columnHelper.accessor('expenses_count', {
        header: 'Gastos',
        cell: info => {
            const count = info.getValue();
            return h('div', { class: 'text-center font-mono' }, count.toString());
        },
    }),
    columnHelper.accessor('last_expense_date', {
        header: 'Último Gasto',
        cell: info => {
            const date = info.getValue();
            return date ? new Date(date).toLocaleDateString('es-CO') : '-';
        },
    }),
    columnHelper.accessor('status_badge', {
        header: 'Estado',
        cell: info => {
            const badge = info.getValue();
            return h(Badge, {
                class: badge.class
            }, badge.text);
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: info => {
            const supplier = info.row.original;
            const actions = [
                {
                    icon: Eye,
                    label: 'Ver',
                    onClick: () => router.visit(`/suppliers/${supplier.id}`),
                },
                {
                    icon: Edit,
                    label: 'Editar',
                    onClick: () => router.visit(`/suppliers/${supplier.id}/edit`),
                },
                {
                    icon: Copy,
                    label: 'Duplicar',
                    onClick: () => duplicateSupplier(supplier.id),
                },
            ];

            if (!supplier.is_active) {
                actions.push({
                    icon: CheckCircle,
                    label: 'Activar',
                    onClick: () => toggleSupplierStatus(supplier.id, true),
                });
            } else {
                actions.push({
                    icon: XCircle,
                    label: 'Desactivar',
                    onClick: () => toggleSupplierStatus(supplier.id, false),
                });
            }

            if (supplier.expenses_count === 0) {
                actions.push({
                    icon: Trash2,
                    label: 'Eliminar',
                    onClick: () => deleteSupplier(supplier.id),
                    variant: 'destructive',
                });
            }

            return h(DropdownAction, { actions });
        },
    }),
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});

const table = useVueTable({
    get data() { return data.value; },
    get columns() { return columns; },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    state: {
        get sorting() { return sorting.value; },
        get columnFilters() { return columnFilters.value; },
        get columnVisibility() { return columnVisibility.value; },
    },
});

// Filter functions
const applyFilters = () => {
    const params: any = {};

    if (search.value) params.search = search.value;
    if (statusFilter.value !== 'all') params.status = statusFilter.value;
    if (documentTypeFilter.value !== 'all') params.document_type = documentTypeFilter.value;
    if (cityFilter.value) params.city = cityFilter.value;

    router.get('/suppliers', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    documentTypeFilter.value = 'all';
    cityFilter.value = '';
    router.get('/suppliers', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch for filter changes
watch([search, statusFilter, documentTypeFilter, cityFilter], () => {
    // Debounce the search
    if (search.value !== props.filters?.search) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    } else {
        applyFilters();
    }
});

let searchTimeout: number;

// Action functions
const toggleSupplierStatus = (supplierId: number, active: boolean) => {
    const action = active ? 'activar' : 'desactivar';
    if (confirm(`¿Está seguro de que desea ${action} este proveedor?`)) {
        router.post(`/suppliers/${supplierId}/toggle-status`, {
            is_active: active
        }, {
            onSuccess: () => {
                router.reload();
            }
        });
    }
};

const duplicateSupplier = (supplierId: number) => {
    router.post(`/suppliers/${supplierId}/duplicate`, {}, {
        onSuccess: () => {
            // Will redirect to edit page
        }
    });
};

const deleteSupplier = (supplierId: number) => {
    if (confirm('¿Está seguro de que desea eliminar este proveedor? Esta acción no se puede deshacer.')) {
        router.delete(`/suppliers/${supplierId}`, {
            onSuccess: () => {
                router.reload();
            }
        });
    }
};
</script>

<template>
    <Head title="Proveedores" />

    <AppLayout title="Proveedores" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <ValidationErrors :errors="errors" />

            <!-- Success Alert -->
            <Alert v-if="flashSuccess" class="mb-6 border-green-200 bg-green-50">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- Error Alert -->
            <Alert v-if="flashError" class="mb-6 border-red-200 bg-red-50">
                <XCircle class="h-4 w-4 text-red-600" />
                <AlertDescription class="text-red-800">
                    {{ flashError }}
                </AlertDescription>
            </Alert>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Proveedores</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.total_suppliers || 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Activos</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats?.active_suppliers || 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Inactivos</CardTitle>
                        <XCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-600">{{ stats?.inactive_suppliers || 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Gastos Registrados</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats?.total_expenses || 0) }}</div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-4">
                <!-- Header with actions -->
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-semibold tracking-tight">Proveedores</h2>
                        <p class="text-sm text-muted-foreground">
                            Gestiona todos los proveedores del conjunto
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button asChild size="sm">
                            <Link href="/suppliers/create">
                                <Plus class="mr-2 h-4 w-4" />
                                Nuevo Proveedor
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- Filters -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="space-y-2">
                                <Label for="search">Buscar</Label>
                                <div class="relative">
                                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        v-model="search"
                                        placeholder="Nombre, documento, email..."
                                        class="pl-8"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Estado</Label>
                                <Select v-model="statusFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="active">Activo</SelectItem>
                                        <SelectItem value="inactive">Inactivo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="document_type">Tipo de Documento</Label>
                                <Select v-model="documentTypeFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="NIT">NIT</SelectItem>
                                        <SelectItem value="CC">Cédula de Ciudadanía</SelectItem>
                                        <SelectItem value="CE">Cédula de Extranjería</SelectItem>
                                        <SelectItem value="PA">Pasaporte</SelectItem>
                                        <SelectItem value="RUT">RUT</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="city">Ciudad</Label>
                                <Input
                                    id="city"
                                    v-model="cityFilter"
                                    placeholder="Filtrar por ciudad"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center space-x-2">
                                <Button @click="applyFilters" size="sm">
                                    <Filter class="mr-2 h-4 w-4" />
                                    Aplicar Filtros
                                </Button>
                                <Button @click="clearFilters" variant="outline" size="sm">
                                    <X class="mr-2 h-4 w-4" />
                                    Limpiar
                                </Button>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Columnas
                                        <ChevronDown class="ml-2 h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        v-for="column in table.getAllColumns().filter(column => column.getCanHide())"
                                        :key="column.id"
                                        :checked="column.getIsVisible()"
                                        @update:checked="value => column.toggleVisibility(!!value)"
                                    >
                                        {{ column.columnDef.header }}
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardContent>
                </Card>

                <!-- Data Table -->
                <Card>
                    <Table>
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                    <Button
                                        v-if="!header.isPlaceholder && header.column.getCanSort()"
                                        variant="ghost"
                                        @click="header.column.getToggleSortingHandler()?.($event)"
                                    >
                                        <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                        <ChevronsUpDown class="ml-2 h-4 w-4" />
                                    </Button>
                                    <FlexRender
                                        v-else
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
                                    class="cursor-pointer hover:bg-muted/50"
                                    @click="router.visit(`/suppliers/${row.original.id}/edit`)"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No se encontraron proveedores.
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Pagination -->
                <div v-if="suppliers?.last_page > 1" class="flex items-center justify-between">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Mostrando {{ suppliers.from }} a {{ suppliers.to }} de {{ suppliers.total }} resultados.
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="suppliers.current_page === 1"
                            @click="router.get(window.location.pathname, { ...route().params, page: suppliers.current_page - 1 })"
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="suppliers.current_page === suppliers.last_page"
                            @click="router.get(window.location.pathname, { ...route().params, page: suppliers.current_page + 1 })"
                        >
                            Siguiente
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
