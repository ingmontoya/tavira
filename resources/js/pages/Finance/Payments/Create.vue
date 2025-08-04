<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency } from '@/utils';

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
    items: {
        description: string;
        amount: number;
        concept: string;
    }[];
}

const props = defineProps<{
    apartments: Apartment[];
    preSelectedApartment?: Apartment;
    preSelectedInvoices?: Invoice[];
    paymentMethods: Record<string, string>;
}>();

const form = useForm({
    apartment_id: props.preSelectedApartment?.id?.toString() || '',
    total_amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'bank_transfer',
    reference_number: '',
    notes: '',
});

const pendingInvoices = ref<Invoice[]>([]);
const loadingInvoices = ref(false);

// Load pending invoices when apartment is selected
watch(() => form.apartment_id, async (apartmentId) => {
    if (!apartmentId) {
        pendingInvoices.value = [];
        return;
    }

    // If we have preselected invoices and this is the preselected apartment, use those
    if (props.preSelectedInvoices && 
        props.preSelectedApartment && 
        apartmentId === props.preSelectedApartment.id.toString()) {
        console.log('Using preselected invoices:', props.preSelectedInvoices);
        pendingInvoices.value = props.preSelectedInvoices;
        return;
    }

    // Otherwise, fetch via API
    loadingInvoices.value = true;
    try {
        console.log('Loading pending invoices for apartment:', apartmentId);
        
        // Use fetch instead of axios to ensure proper cookies are sent
        const response = await fetch(`/finance/payments/pending-invoices?apartment_id=${apartmentId}`, {
            method: 'GET',
            credentials: 'same-origin', // This ensures cookies are sent
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Pending invoices response:', data);
        pendingInvoices.value = data.invoices;
    } catch (error) {
        console.error('Error loading pending invoices:', error);
        pendingInvoices.value = [];
    } finally {
        loadingInvoices.value = false;
    }
}, { immediate: true });

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
    form.post('/finance/payments', {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Finanzas', href: '/finances' },
    { title: 'Pagos', href: '/finance/payments' },
    { title: 'Nuevo Pago', href: '/finance/payments/create' },
];
</script>

<template>
    <Head title="Nuevo Pago" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight">Registrar Nuevo Pago</h1>
                <p class="text-muted-foreground">Complete la información del pago. El sistema aplicará automáticamente el pago a las facturas pendientes.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Payment Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Pago</CardTitle>
                            <CardDescription>Detalles básicos del pago recibido</CardDescription>
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
                                <Input
                                    id="payment_date"
                                    v-model="form.payment_date"
                                    type="date"
                                    :disabled="form.processing"
                                />
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
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Observaciones adicionales..."
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.notes" class="text-sm text-red-600">{{ form.errors.notes }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pending Invoices -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Facturas Pendientes</CardTitle>
                            <CardDescription>
                                <span v-if="form.apartment_id">Facturas pendientes del apartamento seleccionado</span>
                                <span v-else>Seleccione un apartamento para ver las facturas pendientes</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!form.apartment_id" class="text-center text-muted-foreground py-8">
                                Seleccione un apartamento para ver las facturas pendientes
                            </div>
                            
                            <div v-else-if="loadingInvoices" class="text-center text-muted-foreground py-8">
                                Cargando facturas...
                            </div>

                            <div v-else-if="pendingInvoices.length === 0" class="text-center text-muted-foreground py-8">
                                No hay facturas pendientes para este apartamento
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
                                                <TableCell>
                                                    <Badge :class="invoice.status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
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
                        <CardDescription>
                            Así es como se aplicará el pago a las facturas pendientes (orden cronológico)
                        </CardDescription>
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
                                                    <div class="text-xs text-muted-foreground">
                                                        Orden: {{ index + 1 }}
                                                    </div>
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
                                                <Badge :class="simulation.willBeFullyPaid ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                                                    {{ simulation.willBeFullyPaid ? 'Pago Completo' : 'Pago Parcial' }}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div v-if="willFullyPay" class="rounded-lg bg-green-50 border border-green-200 p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">
                                            ¡Excelente! Este pago cubrirá todas las facturas pendientes.
                                        </p>
                                        <p v-if="paymentAmount > totalPendingAmount" class="text-sm text-green-700 mt-1">
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
                    <Button type="button" variant="outline" @click="$inertia.visit('/finance/payments')">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Procesando...' : 'Registrar Pago' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>