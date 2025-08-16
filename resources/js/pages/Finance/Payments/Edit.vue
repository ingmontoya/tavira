<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Apartment {
    id: number;
    number: string;
}

interface Invoice {
    id: number;
    invoice_number: string;
    billing_date: string;
    due_date: string;
    total_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
    status_label: string;
    days_overdue: number;
    current_application_amount?: number;
    items: {
        description: string;
        amount: number;
        concept: string;
    }[];
}

interface Payment {
    id: number;
    payment_number: string;
    apartment_id: number;
    total_amount: number | string;
    payment_date: string;
    payment_method: string;
    reference_number?: string;
    notes?: string;
    status: string;
    status_label: string;
    apartment?: {
        id: number;
        number: string;
    };
}

const props = defineProps<{
    payment: Payment;
    apartments: Apartment[];
    paymentMethods: Record<string, string>;
}>();

const form = useForm({
    apartment_id: props.payment.apartment_id.toString(),
    total_amount: props.payment.total_amount.toString(),
    payment_date: props.payment.payment_date,
    payment_method: props.payment.payment_method,
    reference_number: props.payment.reference_number || '',
    notes: props.payment.notes || '',
});

const pendingInvoices = ref<Invoice[]>([]);
const loadingInvoices = ref(false);

// Load pending invoices when apartment is selected
watch(
    () => form.apartment_id,
    async (apartmentId) => {
        if (!apartmentId) {
            pendingInvoices.value = [];
            return;
        }

        loadingInvoices.value = true;
        try {
            const response = await fetch(`/finance/payments/${props.payment.id}/edit-invoices?apartment_id=${apartmentId}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            // console.log('Edit invoices response:', data);
            pendingInvoices.value = data.invoices;
        } catch (error) {
            console.error('Error loading pending invoices:', error);
            pendingInvoices.value = [];
        } finally {
            loadingInvoices.value = false;
        }
    },
    { immediate: true },
);

// Calculate totals
const totalPendingAmount = computed(() => {
    return pendingInvoices.value.reduce((sum, invoice) => sum + invoice.balance_amount, 0);
});

const paymentAmount = computed(() => {
    return parseFloat(form.total_amount) || 0;
});

const remainingBalance = computed(() => {
    return Math.max(0, totalPendingAmount.value - paymentAmount.value);
});

const willFullyPay = computed(() => {
    return paymentAmount.value >= totalPendingAmount.value;
});

// Calculate which invoices will be paid (FIFO simulation)
const paymentSimulation = computed(() => {
    if (!paymentAmount.value || pendingInvoices.value.length === 0) {
        return [];
    }

    let remainingPayment = paymentAmount.value;
    const simulation = [];

    for (const invoice of pendingInvoices.value) {
        if (remainingPayment <= 0) break;

        const amountToApply = Math.min(remainingPayment, invoice.balance_amount);
        remainingPayment -= amountToApply;

        simulation.push({
            invoice,
            amountToApply,
            willBeFullyPaid: amountToApply >= invoice.balance_amount,
        });
    }

    return simulation;
});

const submit = () => {
    form.put(`/finance/payments/${props.payment.id}`, {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Finanzas', href: '/finances' },
    { title: 'Pagos', href: '/finance/payments' },
    { title: props.payment.payment_number, href: `/finance/payments/${props.payment.id}` },
    { title: 'Editar', href: `/finance/payments/${props.payment.id}/edit` },
];
</script>

<template>
    <Head :title="`Editar ${payment.payment_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight">Editar Pago</h1>
                <p class="text-muted-foreground">
                    {{ payment.payment_number }} - Apartamento {{ payment.apartment?.number || 'N/A' }} ({{ payment.status_label }})
                </p>
                <div class="mt-2">
                    <Badge
                        :class="
                            payment.status === 'pendiente'
                                ? 'bg-yellow-100 text-yellow-800'
                                : payment.status === 'aplicado'
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-blue-100 text-blue-800'
                        "
                    >
                        {{ payment.status_label }}
                    </Badge>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Payment Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Pago</CardTitle>
                            <CardDescription>Modifique los detalles del pago recibido</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="apartment_id">Apartamento</Label>
                                <Select id="apartment_id" v-model="form.apartment_id" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione un apartamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                            {{ apartment.number }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.apartment_id" class="text-sm text-red-600">{{ form.errors.apartment_id }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="total_amount">Monto del Pago</Label>
                                <Input
                                    id="total_amount"
                                    v-model="form.total_amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0.00"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.total_amount" class="text-sm text-red-600">{{ form.errors.total_amount }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="payment_date">Fecha del Pago</Label>
                                <Input id="payment_date" v-model="form.payment_date" type="date" :disabled="form.processing" />
                                <div v-if="form.errors.payment_date" class="text-sm text-red-600">{{ form.errors.payment_date }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="payment_method">Método de Pago</Label>
                                <Select id="payment_method" v-model="form.payment_method" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.payment_method" class="text-sm text-red-600">{{ form.errors.payment_method }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="reference_number">Número de Referencia</Label>
                                <Input
                                    id="reference_number"
                                    v-model="form.reference_number"
                                    placeholder="Número de cheque, transferencia, etc."
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.reference_number" class="text-sm text-red-600">{{ form.errors.reference_number }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="notes">Notas</Label>
                                <Textarea id="notes" v-model="form.notes" placeholder="Observaciones adicionales..." :disabled="form.processing" />
                                <div v-if="form.errors.notes" class="text-sm text-red-600">{{ form.errors.notes }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pending Invoices -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Facturas Aplicables</CardTitle>
                            <CardDescription>
                                <span v-if="form.apartment_id"
                                    >Facturas que pueden ser aplicadas con este pago (incluye facturas ya aplicadas por este pago)</span
                                >
                                <span v-else>Seleccione un apartamento para ver las facturas aplicables</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!form.apartment_id" class="py-8 text-center text-muted-foreground">
                                Seleccione un apartamento para ver las facturas aplicables
                            </div>

                            <div v-else-if="loadingInvoices" class="py-8 text-center text-muted-foreground">Cargando facturas...</div>

                            <div v-else-if="pendingInvoices.length === 0" class="py-8 text-center text-muted-foreground">
                                No hay facturas aplicables para este apartamento
                            </div>

                            <div v-else class="space-y-4">
                                <!-- Summary -->
                                <div class="rounded-lg bg-muted p-4">
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-muted-foreground">Total Adeudado:</span>
                                            <div class="font-mono font-medium">{{ formatCurrency(totalPendingAmount) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground">Facturas:</span>
                                            <div class="font-medium">{{ pendingInvoices.length }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Invoices Table -->
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Factura</TableHead>
                                                <TableHead>Vencimiento</TableHead>
                                                <TableHead class="text-right">Saldo</TableHead>
                                                <TableHead class="text-right">Aplicado</TableHead>
                                                <TableHead>Estado</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="invoice in pendingInvoices" :key="invoice.id">
                                                <TableCell>
                                                    <div class="space-y-1">
                                                        <div class="font-mono text-sm">{{ invoice.invoice_number }}</div>
                                                        <div class="text-xs text-muted-foreground">
                                                            {{ new Date(invoice.billing_date).toLocaleDateString('es-CO') }}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="text-sm">
                                                        {{ new Date(invoice.due_date).toLocaleDateString('es-CO') }}
                                                    </div>
                                                    <div v-if="invoice.days_overdue > 0" class="text-xs text-red-600">
                                                        {{ invoice.days_overdue }} días vencida
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <div class="font-mono text-sm">{{ formatCurrency(invoice.balance_amount) }}</div>
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <div v-if="invoice.current_application_amount > 0" class="font-mono text-sm text-blue-600">
                                                        {{ formatCurrency(invoice.current_application_amount) }}
                                                    </div>
                                                    <div v-else class="text-sm text-muted-foreground">-</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        :class="
                                                            invoice.status === 'overdue'
                                                                ? 'bg-red-100 text-red-800'
                                                                : invoice.status === 'pending'
                                                                  ? 'bg-yellow-100 text-yellow-800'
                                                                  : 'bg-green-100 text-green-800'
                                                        "
                                                    >
                                                        {{ invoice.status_label }}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Payment Simulation -->
                <Card v-if="pendingInvoices.length > 0 && paymentAmount > 0">
                    <CardHeader>
                        <CardTitle>Simulación de Aplicación de Pago</CardTitle>
                        <CardDescription> Así es como se aplicará el pago actualizado a las facturas pendientes (orden cronológico) </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Summary -->
                            <div class="grid grid-cols-3 gap-4 rounded-lg bg-muted p-4">
                                <div class="text-center">
                                    <div class="text-sm text-muted-foreground">Monto del Pago</div>
                                    <div class="font-mono text-lg font-medium">{{ formatCurrency(paymentAmount) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-muted-foreground">Total Adeudado</div>
                                    <div class="font-mono text-lg font-medium">{{ formatCurrency(totalPendingAmount) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-muted-foreground">Saldo Restante</div>
                                    <div :class="['font-mono text-lg font-medium', remainingBalance > 0 ? 'text-red-600' : 'text-green-600']">
                                        {{ formatCurrency(remainingBalance) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Application Details -->
                            <div class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Factura</TableHead>
                                            <TableHead class="text-right">Saldo</TableHead>
                                            <TableHead class="text-right">Pago Aplicado</TableHead>
                                            <TableHead>Resultado</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="(simulation, index) in paymentSimulation" :key="simulation.invoice.id">
                                            <TableCell>
                                                <div class="space-y-1">
                                                    <div class="font-mono text-sm">{{ simulation.invoice.invoice_number }}</div>
                                                    <div class="text-xs text-muted-foreground">Orden: {{ index + 1 }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <div class="font-mono text-sm">{{ formatCurrency(simulation.invoice.balance_amount) }}</div>
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <div class="font-mono text-sm font-medium text-green-600">
                                                    {{ formatCurrency(simulation.amountToApply) }}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    :class="simulation.willBeFullyPaid ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"
                                                >
                                                    {{ simulation.willBeFullyPaid ? 'Pago Completo' : 'Pago Parcial' }}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div v-if="willFullyPay" class="rounded-lg border border-green-200 bg-green-50 p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">¡Excelente! Este pago cubrirá todas las facturas pendientes.</p>
                                        <p v-if="paymentAmount > totalPendingAmount" class="mt-1 text-sm text-green-700">
                                            Sobrante: {{ formatCurrency(paymentAmount - totalPendingAmount) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4">
                    <Button type="button" variant="outline" @click="$inertia.visit(`/finance/payments/${payment.id}`)"> Cancelar </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
