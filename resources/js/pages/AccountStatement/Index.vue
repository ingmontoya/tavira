<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    hasAccess: boolean;
    message?: string;
    resident?: {
        full_name: string;
        document_type: string;
        document_number: string;
        email: string;
        phone: string;
        resident_type: string;
        apartment: {
            full_address: string;
            tower: string;
            number: string;
            type_name: string;
            monthly_fee: number;
            payment_status: string;
            payment_status_badge: { text: string; class: string };
            outstanding_balance: number;
            days_overdue: number;
        };
    };
    summary?: {
        total_invoiced: number;
        total_paid: number;
        current_balance: number;
        overdue_amount: number;
        is_current: boolean;
        current_month_invoice: {
            id: number;
            invoice_number: string;
            due_date: string;
            total_amount: number;
            balance_amount: number;
            status: string;
            status_label: string;
            status_badge: { text: string; class: string };
        } | null;
    };
    invoices?: Array<{
        id: number;
        invoice_number: string;
        type: string;
        type_label: string;
        billing_date: string;
        due_date: string;
        billing_period_label: string;
        subtotal: number;
        early_discount: number;
        late_fees: number;
        total_amount: number;
        paid_amount: number;
        balance_amount: number;
        status: string;
        status_label: string;
        status_badge: { text: string; class: string };
        days_overdue: number;
        items: Array<{
            concept_name: string;
            description: string;
            quantity: number;
            unit_price: number;
            total_price: number;
        }>;
        payments: Array<{
            payment_date: string;
            payment_number: string;
            payment_method: string;
            amount_applied: number;
            applied_date: string;
        }>;
    }>;
    payments?: Array<{
        id: number;
        payment_number: string;
        payment_date: string;
        total_amount: number;
        applied_amount: number;
        remaining_amount: number;
        payment_method: string;
        reference_number: string;
        status: string;
        status_label: string;
        status_badge: { text: string; class: string };
        notes: string;
    }>;
    date_range: {
        start_date: string;
        end_date: string;
    };
    available_months: Array<{
        value: string;
        label: string;
        year: number;
        month: number;
    }>;
}>();

const breadcrumbs = [{ label: 'Estado de Cuenta', href: '#', current: true }];

const selectedDateRange = ref({
    start: props.date_range?.start_date || '',
    end: props.date_range?.end_date || '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const pendingInvoices = computed(() => {
    return (
        props.invoices?.filter((invoice) => invoice.status === 'pendiente' || invoice.status === 'vencido' || invoice.status === 'pago_parcial') || []
    );
});

const paidInvoices = computed(() => {
    return props.invoices?.filter((invoice) => invoice.status === 'pagado') || [];
});

const totalPendingAmount = computed(() => {
    return pendingInvoices.value.reduce((sum, invoice) => sum + invoice.balance_amount, 0);
});

const updateDateRange = () => {
    router.get(
        route('account-statement.index'),
        {
            start_date: selectedDateRange.value.start,
            end_date: selectedDateRange.value.end,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const downloadStatement = () => {
    // TODO: Implement PDF download functionality
    console.log('Download statement functionality to be implemented');
};
</script>

<template>
    <Head title="Estado de Cuenta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- No Access Message -->
            <div v-if="!hasAccess" class="mx-auto max-w-4xl">
                <Alert>
                    <Icon name="alert-circle" class="h-4 w-4" />
                    <AlertDescription>
                        {{ message }}
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Account Statement Content -->
            <div v-else class="space-y-6">
                <!-- Header with Resident Info -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Resident Info -->
                    <Card class="p-6 lg:col-span-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Estado de Cuenta</h1>
                                <div class="mt-4 space-y-2">
                                    <p class="text-lg font-semibold">{{ resident.full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ resident.document_type }}: {{ resident.document_number }}</p>
                                    <p class="text-sm text-gray-600">{{ resident.email }}</p>
                                    <p class="text-sm text-gray-600" v-if="resident.phone">{{ resident.phone }}</p>
                                </div>
                                <div class="mt-4">
                                    <p class="font-medium">{{ resident.apartment.full_address }}</p>
                                    <p class="text-sm text-gray-600">{{ resident.apartment.type_name }}</p>
                                    <p class="text-sm text-gray-600">Cuota mensual: {{ formatCurrency(resident.apartment.monthly_fee) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge :class="resident.apartment.payment_status_badge.class">
                                    {{ resident.apartment.payment_status_badge.text }}
                                </Badge>
                                <p class="mt-2 text-sm text-gray-600" v-if="resident.apartment.days_overdue > 0">
                                    {{ resident.apartment.days_overdue }} días de mora
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Current Balance Summary -->
                    <Card class="p-6">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold">Saldo Actual</h3>
                            <p class="text-3xl font-bold" :class="summary.current_balance > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(summary.current_balance) }}
                            </p>
                            <p class="mt-2 text-sm text-gray-600" v-if="summary.overdue_amount > 0">
                                Mora: {{ formatCurrency(summary.overdue_amount) }}
                            </p>
                            <div class="mt-4" v-if="summary.current_month_invoice">
                                <p class="text-sm font-medium">Factura del mes actual</p>
                                <div class="mt-2 rounded-md border p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm">{{ summary.current_month_invoice.invoice_number }}</span>
                                        <Badge :class="summary.current_month_invoice.status_badge.class">
                                            {{ summary.current_month_invoice.status_badge.text }}
                                        </Badge>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-sm">
                                        <span>Vence: {{ formatDate(summary.current_month_invoice.due_date) }}</span>
                                        <span class="font-semibold">{{ formatCurrency(summary.current_month_invoice.balance_amount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="p-4">
                        <div class="flex items-center">
                            <div class="rounded-full bg-blue-100 p-2">
                                <Icon name="file-text" class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Facturado</p>
                                <p class="text-xl font-bold">{{ formatCurrency(summary.total_invoiced) }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-4">
                        <div class="flex items-center">
                            <div class="rounded-full bg-green-100 p-2">
                                <Icon name="dollar-sign" class="h-6 w-6 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Pagado</p>
                                <p class="text-xl font-bold">{{ formatCurrency(summary.total_paid) }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-4">
                        <div class="flex items-center">
                            <div class="rounded-full bg-yellow-100 p-2">
                                <Icon name="clock" class="h-6 w-6 text-yellow-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Facturas Pendientes</p>
                                <p class="text-xl font-bold">{{ pendingInvoices.length }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-4">
                        <div class="flex items-center">
                            <div class="rounded-full bg-red-100 p-2">
                                <Icon name="alert-circle" class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Mora</p>
                                <p class="text-xl font-bold">{{ formatCurrency(summary.overdue_amount) }}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Date Range Filter and Actions -->
                <Card class="p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-sm font-medium">Desde</label>
                                    <input
                                        v-model="selectedDateRange.start"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Hasta</label>
                                    <input
                                        v-model="selectedDateRange.end"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <Button @click="updateDateRange" variant="outline">
                                <Icon name="search" class="mr-2 h-4 w-4" />
                                Filtrar
                            </Button>
                        </div>
                        <Button @click="downloadStatement" variant="outline">
                            <Icon name="download" class="mr-2 h-4 w-4" />
                            Descargar Estado de Cuenta
                        </Button>
                    </div>
                </Card>

                <!-- Invoices and Payments Tabs -->
                <Tabs default-value="pending" class="space-y-4">
                    <TabsList class="grid w-full grid-cols-3">
                        <TabsTrigger value="pending"> Facturas Pendientes ({{ pendingInvoices.length }}) </TabsTrigger>
                        <TabsTrigger value="paid"> Facturas Pagadas ({{ paidInvoices.length }}) </TabsTrigger>
                        <TabsTrigger value="payments"> Pagos Realizados ({{ payments?.length || 0 }}) </TabsTrigger>
                    </TabsList>

                    <!-- Pending Invoices -->
                    <TabsContent value="pending" class="space-y-4">
                        <Card>
                            <div class="p-6">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold">Facturas Pendientes</h3>
                                    <div v-if="pendingInvoices.length > 0" class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">Total a Pagar</p>
                                            <p class="text-xl font-bold text-red-600">{{ formatCurrency(totalPendingAmount) }}</p>
                                        </div>
                                        <Button @click="router.visit(route('finance.payments.create', { apartment_id: resident?.apartment?.id || 0 }))" class="bg-green-600 hover:bg-green-700">
                                            <Icon name="dollar-sign" class="mr-2 h-4 w-4" />
                                            Pagar Todas
                                        </Button>
                                    </div>
                                </div>
                                <div v-if="pendingInvoices.length === 0" class="py-8 text-center text-gray-500">
                                    <Icon name="check-circle" class="mx-auto mb-4 h-12 w-12 text-green-500" />
                                    <p>¡Excelente! No tienes facturas pendientes.</p>
                                </div>
                                <Table v-else>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Factura</TableHead>
                                            <TableHead>Período</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead>Total</TableHead>
                                            <TableHead>Pagado</TableHead>
                                            <TableHead>Saldo</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="invoice in pendingInvoices" :key="invoice.id">
                                            <TableCell class="font-medium">
                                                {{ invoice.invoice_number }}
                                            </TableCell>
                                            <TableCell>{{ invoice.billing_period_label }}</TableCell>
                                            <TableCell>{{ formatDate(invoice.due_date) }}</TableCell>
                                            <TableCell>{{ formatCurrency(invoice.total_amount) }}</TableCell>
                                            <TableCell>{{ formatCurrency(invoice.paid_amount) }}</TableCell>
                                            <TableCell class="font-semibold">{{ formatCurrency(invoice.balance_amount) }}</TableCell>
                                            <TableCell>
                                                <Badge :class="invoice.status_badge.class">
                                                    {{ invoice.status_badge.text }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <Button
                                                        @click="router.visit(route('finance.payments.create', { apartment_id: resident?.apartment?.id || 0, invoice_id: invoice.id }))"
                                                        size="sm"
                                                        variant="outline"
                                                        class="text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    >
                                                        <Icon name="dollar-sign" class="mr-1 h-3 w-3" />
                                                        Pagar
                                                    </Button>
                                                    <Link
                                                        :href="route('account-statement.invoice', invoice.id)"
                                                        class="text-blue-600 hover:text-blue-800"
                                                    >
                                                        <Icon name="eye" class="h-4 w-4" />
                                                    </Link>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </Card>
                    </TabsContent>

                    <!-- Paid Invoices -->
                    <TabsContent value="paid" class="space-y-4">
                        <Card>
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-semibold">Facturas Pagadas</h3>
                                <div v-if="paidInvoices.length === 0" class="py-8 text-center text-gray-500">
                                    <Icon name="file-text" class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <p>No se encontraron facturas pagadas en el período seleccionado.</p>
                                </div>
                                <Table v-else>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Factura</TableHead>
                                            <TableHead>Período</TableHead>
                                            <TableHead>Fecha Pago</TableHead>
                                            <TableHead>Total</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="invoice in paidInvoices" :key="invoice.id">
                                            <TableCell class="font-medium">
                                                {{ invoice.invoice_number }}
                                            </TableCell>
                                            <TableCell>{{ invoice.billing_period_label }}</TableCell>
                                            <TableCell>{{ formatDate(invoice.billing_date) }}</TableCell>
                                            <TableCell>{{ formatCurrency(invoice.total_amount) }}</TableCell>
                                            <TableCell>
                                                <Badge :class="invoice.status_badge.class">
                                                    {{ invoice.status_badge.text }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Link
                                                    :href="route('account-statement.invoice', invoice.id)"
                                                    class="text-blue-600 hover:text-blue-800"
                                                >
                                                    <Icon name="eye" class="h-4 w-4" />
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </Card>
                    </TabsContent>

                    <!-- Payments -->
                    <TabsContent value="payments" class="space-y-4">
                        <Card>
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-semibold">Pagos Realizados</h3>
                                <div v-if="!payments || payments.length === 0" class="py-8 text-center text-gray-500">
                                    <Icon name="credit-card" class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <p>No se encontraron pagos en el período seleccionado.</p>
                                </div>
                                <Table v-else>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Pago</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Método</TableHead>
                                            <TableHead>Monto Total</TableHead>
                                            <TableHead>Aplicado</TableHead>
                                            <TableHead>Disponible</TableHead>
                                            <TableHead>Estado</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="payment in payments" :key="payment.id">
                                            <TableCell class="font-medium">
                                                {{ payment.payment_number }}
                                            </TableCell>
                                            <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                                            <TableCell>{{ payment.payment_method }}</TableCell>
                                            <TableCell>{{ formatCurrency(payment.total_amount) }}</TableCell>
                                            <TableCell>{{ formatCurrency(payment.applied_amount) }}</TableCell>
                                            <TableCell>{{ formatCurrency(payment.remaining_amount) }}</TableCell>
                                            <TableCell>
                                                <Badge :class="payment.status_badge.class">
                                                    {{ payment.status_badge.text }}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
