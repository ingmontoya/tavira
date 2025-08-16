<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    Apartment,
    CreateInvoiceEmailBatchData,
    EligibleInvoice,
    EligibleInvoiceFilters,
    EligibleInvoicesResponse,
    EmailTemplate,
} from '@/types';
import { cn, valueUpdater } from '@/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ArrowLeft, CheckCircle, ChevronsUpDown, Eye, FileText, Filter, Mail, Plus, Search, X, XCircle } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Pagos',
        href: '/payments',
    },
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Envío por Email',
        href: '/invoices/email',
    },
    {
        title: 'Crear Lote',
        href: '/invoices/email/create',
    },
];

interface Props {
    eligibleInvoices: EligibleInvoicesResponse;
    apartments: Apartment[];
    emailTemplates: EmailTemplate[];
    filters?: EligibleInvoiceFilters;
}

const props = defineProps<Props>();

// Debug props
console.log('Props received:', props);
console.log('Email templates:', props.emailTemplates);

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Form setup
const form = useForm<CreateInvoiceEmailBatchData>({
    name: '',
    description: '',
    invoice_ids: [],
    template_id: undefined,
    send_immediately: false,
});

// Custom filters state
const customFilters = ref<EligibleInvoiceFilters>({
    search: props.filters?.search || '',
    apartment_id: props.filters?.apartment_id || 'all',
    status: props.filters?.status || 'all',
    type: props.filters?.type || 'all',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref<Record<string | number, boolean>>({});

// Initialize row selection properly
console.log('Initial row selection state:', rowSelection.value);

// Step state
const currentStep = ref(1);
const totalSteps = 3;

// Column helper
const columnHelper = createColumnHelper<EligibleInvoice>();

const columns = [
    {
        id: 'select',
        header: 'Seleccionar',
        accessorKey: 'id',
        enableSorting: false,
        enableHiding: false,
        cell: ({ row }) => {
            const invoiceId = row.original.id;
            return `<div class="flex items-center justify-center">
                <input 
                    type="checkbox" 
                    data-invoice-id="${invoiceId}"
                    ${isInvoiceSelected(invoiceId) ? 'checked' : ''}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer invoice-checkbox"
                />
            </div>`;
        },
    },
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
    columnHelper.accessor('apartment_number', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const invoice = row.original;
            console.log('Invoice data:', invoice);

            if (!invoice.apartment_number) {
                return h('div', { class: 'flex flex-col' }, [
                    h('span', { class: 'font-medium text-red-600' }, 'Sin apartamento'),
                    h('span', { class: 'text-sm text-muted-foreground' }, 'N/A'),
                ]);
            }
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, invoice.apartment_number || 'Dirección no disponible'),
                h('span', { class: 'text-sm text-muted-foreground' }, `#${invoice.apartment_number || 'N/A'}`),
            ]);
        },
    }),
    columnHelper.accessor('type', {
        header: 'Tipo',
        cell: ({ row }) => {
            const typeLabels = {
                monthly: 'Mensual',
                individual: 'Individual',
                late_fee: 'Intereses',
            };
            return h('span', { class: 'text-sm' }, typeLabels[row.original.type] || row.original.type);
        },
    }),
    columnHelper.accessor('billing_period_label', {
        header: 'Período',
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.billing_period_label);
        },
    }),
    columnHelper.accessor('due_date', {
        header: 'Vencimiento',
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
        header: 'Saldo',
        cell: ({ row }) => {
            const balance = parseFloat(row.original.total_amount?.toString() || '0');
            return h(
                'div',
                {
                    class: cn('font-medium', balance <= 0 ? 'text-green-600' : 'text-orange-600'),
                },
                `$${balance.toLocaleString()}`,
            );
        },
    }),
    columnHelper.accessor('status', {
        header: 'Estado',
        cell: ({ row }) => {
            const invoice = row.original;
            const statusLabels = {
                pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
                paid: { text: 'Pagado', class: 'bg-green-100 text-green-800' },
                overdue: { text: 'Vencido', class: 'bg-red-100 text-red-800' },
                partial: { text: 'Pago Parcial', class: 'bg-orange-100 text-orange-800' },
                cancelled: { text: 'Cancelado', class: 'bg-gray-100 text-gray-800' },
            };
            const statusInfo = statusLabels[invoice.status] || { text: invoice.status, class: 'bg-gray-100 text-gray-800' };
            return h(
                Badge,
                {
                    class: statusInfo.class,
                },
                () => statusInfo.text,
            );
        },
    }),
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
                    onClick: () => router.visit(`/invoices/${invoice.id}`),
                },
                () => [h(Eye, { class: 'h-4 w-4' })],
            );
        },
    }),
];

// Computed data for the table
const tableData = computed(() => {
    return props.eligibleInvoices?.data || [];
});

const table = useVueTable({
    get data() {
        return tableData.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    manualPagination: true,
    enableRowSelection: true,
    enableMultiRowSelection: true,
    getRowId: (row) => row.id,
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

// Manual selection management
const manualSelectedIds = ref<number[]>([]);

// Update selected invoices
const updateSelectedInvoices = () => {
    const selectedRows = table.getFilteredSelectedRowModel().rows;
    form.invoice_ids = selectedRows.map((row) => row.original.id);
    console.log('Selected invoice IDs:', form.invoice_ids);
    console.log('Table row selection state:', table.getState().rowSelection);
};

// Manual toggle functions
const toggleInvoiceSelection = (invoiceId: number) => {
    const index = manualSelectedIds.value.indexOf(invoiceId);
    if (index > -1) {
        manualSelectedIds.value.splice(index, 1);
    } else {
        manualSelectedIds.value.push(invoiceId);
    }
    form.invoice_ids = [...manualSelectedIds.value];
    console.log('Manual selection updated:', form.invoice_ids);
};

const toggleAllSelection = () => {
    if (manualSelectedIds.value.length === tableData.value.length) {
        // Deselect all
        manualSelectedIds.value = [];
    } else {
        // Select all
        manualSelectedIds.value = tableData.value.map((invoice) => invoice.id);
    }
    form.invoice_ids = [...manualSelectedIds.value];
    console.log('Manual select all updated:', form.invoice_ids);
};

const isInvoiceSelected = (invoiceId: number) => {
    return manualSelectedIds.value.includes(invoiceId);
};

const areAllSelected = computed(() => {
    return tableData.value.length > 0 && manualSelectedIds.value.length === tableData.value.length;
});

const areSomeSelected = computed(() => {
    return manualSelectedIds.value.length > 0 && manualSelectedIds.value.length < tableData.value.length;
});

// Handle select all change
const handleSelectAllChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    console.log('Select all changed:', target.checked);

    if (target.checked) {
        // Select all
        manualSelectedIds.value = tableData.value.map((invoice) => invoice.id);
    } else {
        // Deselect all
        manualSelectedIds.value = [];
    }

    form.invoice_ids = [...manualSelectedIds.value];
    console.log('Select all updated:', form.invoice_ids);
};

// Handle individual checkbox change
const handleCheckboxChange = (event: Event, invoiceId: number) => {
    const target = event.target as HTMLInputElement;
    console.log('Individual checkbox changed:', invoiceId, target.checked);
    toggleInvoiceSelection(invoiceId);
};

// Utility functions for table display
const getTypeLabel = (type: string) => {
    const typeLabels = {
        monthly: 'Mensual',
        individual: 'Individual',
        late_fee: 'Intereses',
    };
    return typeLabels[type] || type;
};

const getStatusLabel = (status: string) => {
    const statusLabels = {
        pending: 'Pendiente',
        paid: 'Pagado',
        overdue: 'Vencido',
        partial: 'Pago Parcial',
        cancelled: 'Cancelado',
    };
    return statusLabels[status] || status;
};

const getStatusBadgeClass = (status: string) => {
    const statusClasses = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        partial: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const formatAmount = (amount: string | number) => {
    return parseFloat(amount.toString()).toLocaleString();
};

const isOverdue = (invoice: any) => {
    const dueDate = new Date(invoice.due_date);
    return invoice.status === 'overdue' || (invoice.status === 'pending' && dueDate < new Date());
};

// Watch for selection changes
watch(
    rowSelection,
    (newSelection) => {
        console.log('Row selection changed:', newSelection);
        updateSelectedInvoices();
    },
    { deep: true },
);

// Apply filters
const applyFilters = () => {
    const params: Record<string, string> = {};

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.apartment_id && customFilters.value.apartment_id !== 'all') params.apartment_id = customFilters.value.apartment_id;
    if (customFilters.value.status && customFilters.value.status !== 'all') params.status = customFilters.value.status;
    if (customFilters.value.type && customFilters.value.type !== 'all') params.type = customFilters.value.type;
    if (customFilters.value.date_from) params.date_from = customFilters.value.date_from;
    if (customFilters.value.date_to) params.date_to = customFilters.value.date_to;

    router.get('/invoices/email/create', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    customFilters.value = {
        search: '',
        apartment_id: 'all',
        status: 'all',
        type: 'all',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

// Submit form
const submitForm = () => {
    // Create filters based on selected invoices
    const filters = {
        invoice_ids: manualSelectedIds.value,
        apartment_ids: [...new Set(selectedInvoices.value.map((inv) => inv.apartment_id))],
        // Add other filters if needed
    };

    // Update form data to match controller expectations
    const submitData = {
        name: form.name,
        description: form.description,
        filters: filters,
        email_settings: {
            template: form.template_id,
            include_pdf: true,
        },
        schedule_send: form.send_immediately,
    };

    console.log('Submitting data:', submitData);

    // Submit using router.post
    router.post('/invoices/email', submitData);
};

// Step navigation
const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
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

// Filter options
const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'partial', label: 'Pago parcial' },
    { value: 'overdue', label: 'Vencido' },
];

const typeOptions = [
    { value: 'all', label: 'Todos los tipos' },
    { value: 'monthly', label: 'Mensual' },
    { value: 'individual', label: 'Individual' },
    { value: 'late_fee', label: 'Intereses' },
];

// Computed properties
const selectedCount = computed(() => manualSelectedIds.value.length);
const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.name.trim().length > 0;
        case 2:
            return selectedCount.value > 0;
        case 3:
            return true;
        default:
            return false;
    }
});

// Get selected invoices for preview
const selectedInvoices = computed(() => {
    return tableData.value.filter((invoice) => manualSelectedIds.value.includes(invoice.id));
});

// Calculate total amount
const totalAmount = computed(() => {
    return selectedInvoices.value.reduce((sum, invoice) => sum + parseFloat(invoice.total_amount.toString()), 0);
});
</script>

<template>
    <Head title="Crear Lote de Envío" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Crear Lote de Envío de Facturas</h1>
                    <p class="text-muted-foreground">
                        Paso {{ currentStep }} de {{ totalSteps }} -
                        <span v-if="currentStep === 1">Información del lote</span>
                        <span v-else-if="currentStep === 2">Seleccionar facturas</span>
                        <span v-else>Revisar y confirmar</span>
                    </p>
                </div>

                <Button variant="outline" asChild>
                    <Link href="/invoices/email">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Link>
                </Button>
            </div>

            <!-- Progress Bar -->
            <div class="h-2 w-full rounded-full bg-gray-200">
                <div
                    class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                    :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
                ></div>
            </div>

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

            <!-- Step 1: Batch Information -->
            <Card v-if="currentStep === 1" class="p-6">
                <CardHeader class="pb-4">
                    <CardTitle class="flex items-center space-x-2">
                        <FileText class="h-5 w-5" />
                        <span>Información del Lote</span>
                    </CardTitle>
                    <CardDescription> Define el nombre y descripción del lote de envío </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nombre del Lote *</Label>
                        <Input id="name" v-model="form.name" placeholder="Ej: Facturas Enero 2024" :class="{ 'border-red-500': form.errors.name }" />
                        <div v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Descripción (opcional)</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Descripción adicional del lote..." rows="3" />
                    </div>
                </CardContent>

                <CardFooter class="pt-4">
                    <Button @click="nextStep" :disabled="!canProceed" class="ml-auto"> Siguiente: Seleccionar Facturas </Button>
                </CardFooter>
            </Card>

            <!-- Step 2: Invoice Selection -->
            <template v-if="currentStep === 2">
                <!-- Filters -->
                <Card class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Filter class="h-4 w-4" />
                            <Label class="text-sm font-medium">Filtros</Label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                            <!-- Search -->
                            <div class="space-y-2">
                                <Label for="search">Buscar</Label>
                                <div class="relative">
                                    <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                    <Input id="search" placeholder="Número..." v-model="customFilters.search" class="pl-8" />
                                </div>
                            </div>

                            <!-- Apartment Filter -->
                            <div class="space-y-2">
                                <Label>Apartamento</Label>
                                <Select v-model="customFilters.apartment_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Apartamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                            {{ apartment.full_address }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Status Filter -->
                            <div class="space-y-2">
                                <Label>Estado</Label>
                                <Select v-model="customFilters.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Estado" />
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
                                        <SelectValue placeholder="Tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in typeOptions" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Date From -->
                            <div class="space-y-2">
                                <Label for="date_from">Desde</Label>
                                <Input id="date_from" type="date" v-model="customFilters.date_from" />
                            </div>

                            <!-- Date To -->
                            <div class="space-y-2">
                                <Label for="date_to">Hasta</Label>
                                <Input id="date_to" type="date" v-model="customFilters.date_to" />
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex items-center justify-between">
                            <Button @click="clearFilters" variant="outline" size="sm">
                                <X class="mr-2 h-4 w-4" />
                                Limpiar Filtros
                            </Button>

                            <div class="text-sm text-muted-foreground">
                                <strong>{{ selectedCount }} de {{ tableData.length }}</strong> facturas seleccionadas
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Invoice Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Facturas Elegibles</CardTitle>
                        <CardDescription> Selecciona las facturas que deseas incluir en el lote de envío </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <!-- Select All Checkbox -->
                        <div class="mb-4 flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="select-all"
                                :checked="areAllSelected"
                                @change="handleSelectAllChange"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="select-all" class="text-sm font-medium"> Seleccionar todas las facturas ({{ tableData.length }}) </label>
                        </div>

                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-16">Seleccionar</TableHead>
                                        <TableHead>Número</TableHead>
                                        <TableHead>Apartamento</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Período</TableHead>
                                        <TableHead>Vencimiento</TableHead>
                                        <TableHead>Saldo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-if="tableData.length">
                                        <TableRow
                                            v-for="invoice in tableData"
                                            :key="invoice.id"
                                            class="transition-colors hover:bg-muted/50"
                                            :class="{ 'bg-blue-50': isInvoiceSelected(invoice.id) }"
                                        >
                                            <TableCell class="text-center">
                                                <input
                                                    type="checkbox"
                                                    :checked="isInvoiceSelected(invoice.id)"
                                                    @change="(e) => handleCheckboxChange(e, invoice.id)"
                                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                            </TableCell>
                                            <TableCell class="font-medium">{{ invoice.invoice_number }}</TableCell>
                                            <TableCell>
                                                <div class="flex flex-col">
                                                    <span class="font-medium">{{ invoice.apartment_number || 'Sin apartamento' }}</span>
                                                    <span class="text-sm text-muted-foreground">#{{ invoice.apartment_number || 'N/A' }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span class="text-sm">{{ getTypeLabel(invoice.type) }}</span>
                                            </TableCell>
                                            <TableCell>
                                                <span class="text-sm">{{ invoice.billing_period_label }}</span>
                                            </TableCell>
                                            <TableCell>
                                                <div :class="isOverdue(invoice) ? 'font-medium text-red-600' : 'text-muted-foreground'">
                                                    {{ formatDate(invoice.due_date) }}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="font-medium text-orange-600">${{ formatAmount(invoice.total_amount) }}</div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge :class="getStatusBadgeClass(invoice.status)">
                                                    {{ getStatusLabel(invoice.status) }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Button variant="ghost" size="sm" @click="() => router.visit(`/invoices/${invoice.id}`)">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                    <template v-else>
                                        <TableRow>
                                            <TableCell :colSpan="9" class="h-24 text-center"> No se encontraron facturas elegibles. </TableCell>
                                        </TableRow>
                                    </template>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-between">
                        <Button @click="prevStep" variant="outline"> Anterior: Información del Lote </Button>

                        <Button @click="nextStep" :disabled="!canProceed"> Siguiente: Revisar ({{ selectedCount }}) </Button>
                    </CardFooter>
                </Card>
            </template>

            <!-- Step 3: Review and Confirm -->
            <template v-if="currentStep === 3">
                <div class="grid gap-4 lg:grid-cols-2">
                    <!-- Batch Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileText class="h-5 w-5" />
                                <span>Resumen del Lote</span>
                            </CardTitle>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">Nombre</Label>
                                <div class="text-sm">{{ form.name }}</div>
                            </div>

                            <div v-if="form.description" class="space-y-2">
                                <Label class="text-sm font-medium">Descripción</Label>
                                <div class="text-sm text-muted-foreground">{{ form.description }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-sm font-medium">Template de Email</Label>
                                <Select v-model="form.template_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar template" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="template in props.emailTemplates" :key="template.id" :value="template.id.toString()">
                                            {{ template.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="send_immediately" v-model="form.send_immediately" />
                                <Label for="send_immediately" class="text-sm"> Enviar inmediatamente después de crear </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Selected Invoices Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Mail class="h-5 w-5" />
                                <span>Facturas Seleccionadas</span>
                            </CardTitle>
                            <CardDescription> {{ selectedCount }} facturas por un total de ${{ totalAmount.toLocaleString() }} </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                <div
                                    v-for="invoice in selectedInvoices"
                                    :key="invoice.id"
                                    class="flex items-center justify-between border-b py-2 last:border-0"
                                >
                                    <div>
                                        <div class="text-sm font-medium">{{ invoice.invoice_number }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ invoice.apartment_number || 'Sin apartamento asignado' }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium">${{ parseFloat(invoice.total_amount.toString()).toLocaleString() }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Action Buttons -->
                <Card>
                    <CardFooter class="flex justify-between pt-6">
                        <Button @click="prevStep" variant="outline"> Anterior: Seleccionar Facturas </Button>

                        <Button @click="submitForm" :disabled="form.processing" class="bg-green-600 hover:bg-green-700">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creando...' : 'Crear Lote de Envío' }}
                        </Button>
                    </CardFooter>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
