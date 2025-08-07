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
import { CalendarDays, CheckCircle, ChevronDown, ChevronsUpDown, Copy, CreditCard, Edit, Eye, Filter, Plus, Search, Settings, Trash2, X, XCircle, AlertTriangle } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { valueUpdater, formatCurrency } from '../../utils';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Egresos',
        href: '/expenses',
    },
];

interface Expense {
    id: number;
    expense_number: string;
    vendor_name: string;
    description: string;
    expense_date: string;
    due_date: string;
    total_amount: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    is_overdue: boolean;
    days_overdue: number;
    expense_category: {
        id: number;
        name: string;
        color: string;
        icon: string;
    };
    created_by: {
        id: number;
        name: string;
    };
    approved_by?: {
        id: number;
        name: string;
    };
    debit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
    credit_account?: {
        id: number;
        code: string;
        name: string;
        full_name: string;
    };
}

interface ExpenseCategory {
    id: number;
    name: string;
    color: string;
    icon: string;
}

interface Stats {
    total_pending: number;
    total_approved: number;
    total_paid: number;
    overdue_count: number;
}

const props = defineProps<{
    expenses: {
        data: Expense[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    categories: ExpenseCategory[];
    stats: Stats;
    filters?: {
        search?: string;
        status?: string;
        category_id?: string;
        date_from?: string;
        date_to?: string;
        vendor?: string;
    };
}>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const data = computed(() => props.expenses.data);

// Search and filters
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const categoryFilter = ref(props.filters?.category_id || 'all');
const dateFromFilter = ref(props.filters?.date_from || '');
const dateToFilter = ref(props.filters?.date_to || '');
const vendorFilter = ref(props.filters?.vendor || '');

// Table setup
const columnHelper = createColumnHelper<Expense>();

const columns = [
    columnHelper.accessor('expense_number', {
        header: 'Número',
        cell: info => info.getValue(),
    }),
    columnHelper.accessor('vendor_name', {
        header: 'Proveedor',
        cell: info => info.getValue(),
    }),
    columnHelper.accessor('description', {
        header: 'Descripción',
        cell: info => {
            const value = info.getValue();
            return value.length > 50 ? value.substring(0, 50) + '...' : value;
        },
    }),
    columnHelper.accessor('expense_category.name', {
        header: 'Categoría',
        cell: info => {
            const category = info.row.original.expense_category;
            return h('div', { class: 'flex items-center gap-2' }, [
                h('div', {
                    class: 'w-3 h-3 rounded-full',
                    style: { backgroundColor: category.color }
                }),
                h('span', category.name)
            ]);
        },
    }),
    columnHelper.accessor('expense_date', {
        header: 'Fecha',
        cell: info => new Date(info.getValue()).toLocaleDateString('es-CO'),
    }),
    columnHelper.accessor('total_amount', {
        header: 'Valor',
        cell: info => formatCurrency(info.getValue()),
    }),
    columnHelper.accessor('status_badge', {
        header: 'Estado',
        cell: info => {
            const badge = info.getValue();
            const expense = info.row.original;

            return h('div', { class: 'flex items-center gap-2' }, [
                h(Badge, {
                    class: badge.class
                }, badge.text),
                expense.is_overdue ? h(AlertTriangle, {
                    class: 'w-4 h-4 text-red-500',
                    title: `Vencido ${expense.days_overdue} días`
                }) : null
            ]);
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Acciones',
        cell: info => {
            const expense = info.row.original;
            const actions = [
                {
                    icon: Eye,
                    label: 'Ver',
                    onClick: () => router.visit(`/expenses/${expense.id}`),
                },
            ];

            if (['borrador', 'pendiente', 'rechazado'].includes(expense.status)) {
                actions.push({
                    icon: Edit,
                    label: 'Editar',
                    onClick: () => router.visit(`/expenses/${expense.id}/edit`),
                });
            }

            if (expense.status === 'pendiente' && page.props.auth?.user?.role === 'administrator') {
                actions.push({
                    icon: CheckCircle,
                    label: 'Aprobar',
                    onClick: () => approveExpense(expense.id),
                });
            }

            if (expense.status === 'aprobado') {
                actions.push({
                    icon: CreditCard,
                    label: 'Marcar como Pagado',
                    onClick: () => markAsPaid(expense.id),
                });
            }

            actions.push({
                icon: Copy,
                label: 'Duplicar',
                onClick: () => duplicateExpense(expense.id),
            });

            if (['borrador', 'rechazado'].includes(expense.status)) {
                actions.push({
                    icon: Trash2,
                    label: 'Eliminar',
                    onClick: () => deleteExpense(expense.id),
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
    if (categoryFilter.value !== 'all') params.category_id = categoryFilter.value;
    if (dateFromFilter.value) params.date_from = dateFromFilter.value;
    if (dateToFilter.value) params.date_to = dateToFilter.value;
    if (vendorFilter.value) params.vendor = vendorFilter.value;

    router.get('/expenses', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    categoryFilter.value = 'all';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    vendorFilter.value = '';
    router.get('/expenses', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch for filter changes
watch([search, statusFilter, categoryFilter, dateFromFilter, dateToFilter, vendorFilter], () => {
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
const approveExpense = (expenseId: number) => {
    if (confirm('¿Está seguro de que desea aprobar este gasto?')) {
        router.post(`/expenses/${expenseId}/approve`, {}, {
            onSuccess: () => {
                // Refresh the page
                router.reload();
            }
        });
    }
};

const markAsPaid = (expenseId: number) => {
    const paymentMethod = prompt('Método de pago (opcional):');
    const paymentReference = prompt('Referencia de pago (opcional):');

    if (confirm('¿Está seguro de que desea marcar este gasto como pagado?')) {
        router.post(`/expenses/${expenseId}/mark-as-paid`, {
            payment_method: paymentMethod,
            payment_reference: paymentReference,
        }, {
            onSuccess: () => {
                router.reload();
            }
        });
    }
};

const duplicateExpense = (expenseId: number) => {
    router.post(`/expenses/${expenseId}/duplicate`, {}, {
        onSuccess: () => {
            // Will redirect to edit page
        }
    });
};

const deleteExpense = (expenseId: number) => {
    if (confirm('¿Está seguro de que desea eliminar este gasto? Esta acción no se puede deshacer.')) {
        router.delete(`/expenses/${expenseId}`, {
            onSuccess: () => {
                router.reload();
            }
        });
    }
};
</script>

<template>
    <Head title="Egresos" />

    <AppLayout title="Egresos" :breadcrumbs="breadcrumbs">
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
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.total_pending) }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aprobados</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.total_approved) }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pagados</CardTitle>
                        <CreditCard class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.total_paid) }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Vencidos</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.overdue_count }}</div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-4">
                <!-- Header with actions -->
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-semibold tracking-tight">Egresos</h2>
                        <p class="text-sm text-muted-foreground">
                            Gestiona todos los gastos del conjunto
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href="/expense-categories">
                                <Settings class="mr-2 h-4 w-4" />
                                Categorías
                            </Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href="/expenses/create">
                                <Plus class="mr-2 h-4 w-4" />
                                Nuevo Gasto
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
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div class="space-y-2">
                                <Label for="search">Buscar</Label>
                                <div class="relative">
                                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        v-model="search"
                                        placeholder="Número, proveedor, descripción..."
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
                                        <SelectItem value="borrador">Borrador</SelectItem>
                                        <SelectItem value="pendiente">Pendiente</SelectItem>
                                        <SelectItem value="aprobado">Aprobado</SelectItem>
                                        <SelectItem value="pagado">Pagado</SelectItem>
                                        <SelectItem value="rechazado">Rechazado</SelectItem>
                                        <SelectItem value="cancelado">Cancelado</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="category">Categoría</Label>
                                <Select v-model="categoryFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas las categorías" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas</SelectItem>
                                        <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                            {{ category.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="date-from">Fecha desde</Label>
                                <Input
                                    id="date-from"
                                    v-model="dateFromFilter"
                                    type="date"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="date-to">Fecha hasta</Label>
                                <Input
                                    id="date-to"
                                    v-model="dateToFilter"
                                    type="date"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="vendor">Proveedor</Label>
                                <Input
                                    id="vendor"
                                    v-model="vendorFilter"
                                    placeholder="Nombre del proveedor"
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
                                    @click="router.visit(`/expenses/${row.original.id}/edit`)"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No se encontraron gastos.
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Pagination -->
                <div v-if="expenses.last_page > 1" class="flex items-center justify-between">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Mostrando {{ expenses.from }} a {{ expenses.to }} de {{ expenses.total }} resultados.
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="expenses.current_page === 1"
                            @click="router.get(window.location.pathname, { ...route().params, page: expenses.current_page - 1 })"
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="expenses.current_page === expenses.last_page"
                            @click="router.get(window.location.pathname, { ...route().params, page: expenses.current_page + 1 })"
                        >
                            Siguiente
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
