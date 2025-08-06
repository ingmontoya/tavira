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
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { createColumnHelper, FlexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ArrowLeft, CheckCircle, ChevronsUpDown, Eye, FileText, Filter, Mail, Plus, Search, X, XCircle } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { cn, valueUpdater } from '@/utils';
import type { Invoice, EligibleInvoicesResponse, EligibleInvoiceFilters, Apartment, EmailTemplate, CreateInvoiceEmailBatchData } from '@/types';

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
const rowSelection = ref<Record<string, boolean>>({});

// Step state
const currentStep = ref(1);
const totalSteps = 3;

// Column helper
const columnHelper = createColumnHelper<EligibleInvoicesResponse>();

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
                'onUpdate:checked': (value) => {
                    row.toggleSelected(!!value);
                    updateSelectedInvoices();
                },
                ariaLabel: 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    }),
    columnHelper.accessor('data.invoice_number', {
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
    columnHelper.accessor('data.apartment.full_address', {
        header: 'Apartamento',
        cell: ({ row }) => {
            const apartment = row.original;
            console.log(apartment);

            if (!apartment) {
                return h('div', { class: 'flex flex-col' }, [
                    h('span', { class: 'font-medium text-red-600' }, 'Sin apartamento'),
                    h('span', { class: 'text-sm text-muted-foreground' }, 'N/A'),
                ]);
            }
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, apartment.apartment_number || 'Dirección no disponible'),
                h('span', { class: 'text-sm text-muted-foreground' }, `#${apartment.apartment_number || 'N/A'}`),
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
    columnHelper.accessor('balance_due', {
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
            return h(
                Badge,
                {
                    class: invoice.status_badge?.class,
                },
                () => invoice.status_badge?.text,
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

// Update selected invoices
const updateSelectedInvoices = () => {
    const selectedRows = table.getFilteredSelectedRowModel().rows;
    form.invoice_ids = selectedRows.map((row) => row.original.id);
};

// Apply filters
const applyFilters = () => {
    const params: Record<string, string> = {};

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.apartment_id && customFilters.value.apartment_id !== 'all')
        params.apartment_id = customFilters.value.apartment_id;
    if (customFilters.value.status && customFilters.value.status !== 'all')
        params.status = customFilters.value.status;
    if (customFilters.value.type && customFilters.value.type !== 'all')
        params.type = customFilters.value.type;
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
    form.post('/invoices/email', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
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
const selectedCount = computed(() => form.invoice_ids.length);
const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1: return form.name.trim().length > 0;
        case 2: return selectedCount.value > 0;
        case 3: return true;
        default: return false;
    }
});

// Get selected invoices for preview
const selectedInvoices = computed(() => {
    return tableData.value.filter(invoice => form.invoice_ids.includes(invoice.id));
});

// Calculate total amount
const totalAmount = computed(() => {
    return selectedInvoices.value.reduce((sum, invoice) => sum + parseFloat(invoice.balance_due.toString()), 0);
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
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
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
                    <CardDescription>
                        Define el nombre y descripción del lote de envío
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nombre del Lote *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Ej: Facturas Enero 2024"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <div v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Descripción (opcional)</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Descripción adicional del lote..."
                            rows="3"
                        />
                    </div>
                </CardContent>

                <CardFooter class="pt-4">
                    <Button
                        @click="nextStep"
                        :disabled="!canProceed"
                        class="ml-auto"
                    >
                        Siguiente: Seleccionar Facturas
                    </Button>
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
                                    <Input
                                        id="search"
                                        placeholder="Número..."
                                        v-model="customFilters.search"
                                        class="pl-8"
                                    />
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
                                        <SelectItem
                                            v-for="apartment in apartments"
                                            :key="apartment.id"
                                            :value="apartment.id.toString()"
                                        >
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
                                        <SelectItem
                                            v-for="status in statusOptions"
                                            :key="status.value"
                                            :value="status.value"
                                        >
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
                                        <SelectItem
                                            v-for="type in typeOptions"
                                            :key="type.value"
                                            :value="type.value"
                                        >
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Date From -->
                            <div class="space-y-2">
                                <Label for="date_from">Desde</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    v-model="customFilters.date_from"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="space-y-2">
                                <Label for="date_to">Hasta</Label>
                                <Input
                                    id="date_to"
                                    type="date"
                                    v-model="customFilters.date_to"
                                />
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex items-center justify-between">
                            <Button @click="clearFilters" variant="outline" size="sm">
                                <X class="mr-2 h-4 w-4" />
                                Limpiar Filtros
                            </Button>

                            <div class="text-sm text-muted-foreground">
                                {{ selectedCount }} facturas seleccionadas
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Invoice Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Facturas Elegibles</CardTitle>
                        <CardDescription>
                            Selecciona las facturas que deseas incluir en el lote de envío
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
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
                                        >
                                            <TableCell
                                                v-for="cell in row.getVisibleCells()"
                                                :key="cell.id"
                                            >
                                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
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
                    </CardContent>

                    <CardFooter class="flex justify-between">
                        <Button @click="prevStep" variant="outline">
                            Anterior: Información del Lote
                        </Button>

                        <Button
                            @click="nextStep"
                            :disabled="!canProceed"
                        >
                            Siguiente: Revisar ({{ selectedCount }})
                        </Button>
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
                                        <SelectItem
                                            v-for="template in emailTemplates"
                                            :key="template.id"
                                            :value="template.id.toString()"
                                        >
                                            {{ template.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="send_immediately"
                                    v-model="form.send_immediately"
                                />
                                <Label for="send_immediately" class="text-sm">
                                    Enviar inmediatamente después de crear
                                </Label>
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
                            <CardDescription>
                                {{ selectedCount }} facturas por un total de ${{ totalAmount.toLocaleString() }}
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div
                                    v-for="invoice in selectedInvoices"
                                    :key="invoice.id"
                                    class="flex justify-between items-center py-2 border-b last:border-0"
                                >
                                    <div>
                                        <div class="font-medium text-sm">{{ invoice.invoice_number }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ invoice.apartment?.full_address || 'Sin apartamento asignado' }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium">
                                        ${{ parseFloat(invoice.balance_due.toString()).toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Action Buttons -->
                <Card>
                    <CardFooter class="flex justify-between pt-6">
                        <Button @click="prevStep" variant="outline">
                            Anterior: Seleccionar Facturas
                        </Button>

                        <Button
                            @click="submitForm"
                            :disabled="form.processing"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creando...' : 'Crear Lote de Envío' }}
                        </Button>
                    </CardFooter>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
