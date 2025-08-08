<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Building, CheckCircle, CreditCard, Download, Edit, ExternalLink, Mail, Printer, Receipt, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Detalle de Factura',
        href: '',
    },
];

interface InvoiceItem {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    period_start?: string;
    period_end?: string;
    notes?: string;
    payment_concept?: {
        id: number;
        name: string;
        type: string;
        type_label: string;
    } | null;
}

interface PaymentApplication {
    id: number;
    amount_applied: number;
    applied_date: string;
    status: 'activo' | 'reversado';
    status_label: string;
    payment: {
        id: number;
        payment_number: string;
        total_amount: number;
        payment_date: string;
        payment_method: string;
        payment_method_label: string;
        reference_number?: string;
        created_by?: {
            id: number;
            name: string;
        };
    };
}

interface Invoice {
    id: number;
    invoice_number: string;
    type: string;
    type_label: string;
    billing_date: string;
    due_date: string;
    billing_period_year: number;
    billing_period_month: number;
    billing_period_label: string;
    subtotal: number;
    late_fees: number;
    total_amount: number;
    paid_amount: number;
    balance_due: number;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    paid_date?: string;
    payment_method?: string;
    payment_reference?: string;
    notes?: string;
    days_overdue?: number;
    apartment: {
        id: number;
        number: string;
        tower: string;
        full_address: string;
        apartment_type: {
            id: number;
            name: string;
        };
    };
    items: InvoiceItem[];
    payment_applications: PaymentApplication[];
}

const props = defineProps<{
    invoice: Invoice;
}>();

const { success, error } = useToast();

const showPaymentDialog = ref(false);

const paymentForm = useForm({
    amount: props.invoice.balance_due,
    payment_method: '',
    payment_reference: '',
});

const submitPayment = () => {
    paymentForm.post(`/invoices/${props.invoice.id}/mark-paid`, {
        onSuccess: () => {
            showPaymentDialog.value = false;
            paymentForm.reset();
        },
    });
};

const deleteInvoice = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta factura?')) {
        router.delete(`/invoices/${props.invoice.id}`, {
            onSuccess: () => {
                router.visit('/invoices');
            },
        });
    }
};

// PDF Download
const downloadPDF = () => {
    window.open(`/invoices/${props.invoice.id}/pdf`, '_blank');
};

// Print functionality
const printInvoice = () => {
    window.print();
};

// Email functionality
const sendByEmail = () => {
    router.post(
        `/invoices/${props.invoice.id}/send-email`,
        {},
        {
            onSuccess: () => {
                success('Factura enviada por correo electrónico exitosamente');
            },
            onError: (errors) => {
                console.error('Error sending email:', errors);
                error('Error al enviar la factura por correo electrónico');
            },
        },
    );
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const canEdit = !['paid', 'cancelled'].includes(props.invoice.status);
const canDelete = !['paid', 'partial'].includes(props.invoice.status);
const canReceivePayment = ['pending', 'partial', 'overdue'].includes(props.invoice.status) && props.invoice.balance_due > 0;
const isOverdue = props.invoice.status === 'overdue' || (props.invoice.status === 'pending' && new Date(props.invoice.due_date) < new Date());
</script>

<template>
    <Head :title="`Factura ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Status and Actions -->
            <div class="flex items-center justify-between py-4">
                <Badge :class="invoice.status_badge.class" class="px-4 py-2 text-lg">
                    {{ invoice.status_badge.text }}
                </Badge>

                <div class="flex items-center space-x-2">
                    <Button variant="outline" size="sm" @click="printInvoice">
                        <Printer class="mr-2 h-4 w-4" />
                        Imprimir
                    </Button>

                    <Button variant="outline" size="sm" @click="downloadPDF">
                        <Download class="mr-2 h-4 w-4" />
                        PDF
                    </Button>

                    <Button variant="outline" size="sm" @click="sendByEmail">
                        <Mail class="mr-2 h-4 w-4" />
                        Enviar Email
                    </Button>

                    <Button v-if="canEdit" asChild variant="outline" size="sm">
                        <Link :href="`/invoices/${invoice.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>

                    <Button v-if="canDelete" @click="deleteInvoice" variant="outline" size="sm">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>

                </div>
            </div>

            <!-- Overdue Alert -->
            <div v-if="isOverdue" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex items-center space-x-2">
                    <AlertTriangle class="h-5 w-5 text-red-600" />
                    <div>
                        <h3 class="font-medium text-red-800">Factura Vencida</h3>
                        <p class="text-sm text-red-700">
                            Esta factura venció el {{ formatDate(invoice.due_date) }}
                            <span v-if="invoice.days_overdue">
                                ({{ invoice.days_overdue }} día{{ invoice.days_overdue === 1 ? '' : 's' }} de retraso)
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Invoice Details -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Receipt class="h-5 w-5" />
                                <span>Información de la Factura</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Número de Factura</Label>
                                    <p class="font-medium">{{ invoice.invoice_number }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Tipo</Label>
                                    <p>{{ invoice.type_label }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Facturación</Label>
                                    <p>{{ formatDate(invoice.billing_date) }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Vencimiento</Label>
                                    <p :class="isOverdue ? 'font-medium text-red-600' : ''">
                                        {{ formatDate(invoice.due_date) }}
                                    </p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Período de Facturación</Label>
                                    <p>{{ invoice.billing_period_label }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                                    <Badge :class="invoice.status_badge.class">
                                        {{ invoice.status_badge.text }}
                                    </Badge>
                                </div>
                            </div>

                            <div v-if="invoice.notes">
                                <Label class="text-sm font-medium text-muted-foreground">Notas</Label>
                                <p class="text-sm">{{ invoice.notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Apartment Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Building class="h-5 w-5" />
                                <span>Información del Apartamento</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Apartamento</Label>
                                    <p class="font-medium">Tore {{ invoice.apartment.tower }} - {{ invoice.apartment.number }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Tipo</Label>
                                    <p>{{ invoice.apartment.apartment_type.name }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Invoice Items -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Conceptos Facturados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="item in invoice.items" :key="item.id" class="rounded-lg border p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-medium">{{ item.description }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ item.payment_concept?.type_label || 'Sin tipo' }}</p>
                                            <div v-if="item.period_start && item.period_end" class="mt-1 text-xs text-muted-foreground">
                                                Período: {{ formatDate(item.period_start) }} - {{ formatDate(item.period_end) }}
                                            </div>
                                            <div v-if="item.notes" class="mt-1 text-xs text-muted-foreground">
                                                {{ item.notes }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-muted-foreground">
                                                {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                                            </div>
                                            <div class="font-medium">{{ formatCurrency(item.total_price) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Payment Summary -->
                <div class="space-y-6">
                    <!-- Totals -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen de Pagos</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>

                            <div v-if="invoice.late_fees > 0" class="flex justify-between text-red-600">
                                <span>Intereses de mora:</span>
                                <span>{{ formatCurrency(invoice.late_fees) }}</span>
                            </div>

                            <Separator />

                            <div class="flex justify-between text-lg font-medium">
                                <span>Total:</span>
                                <span>{{ formatCurrency(invoice.total_amount) }}</span>
                            </div>

                            <div v-if="invoice.paid_amount > 0" class="flex justify-between text-green-600">
                                <span>Pagado:</span>
                                <span>-{{ formatCurrency(invoice.paid_amount) }}</span>
                            </div>

                            <div v-if="invoice.balance_due > 0" class="flex justify-between font-medium text-orange-600">
                                <span>Saldo pendiente:</span>
                                <span>{{ formatCurrency(invoice.balance_due) }}</span>
                            </div>

                            <div v-else-if="invoice.status === 'paid'" class="flex justify-between font-medium text-green-600">
                                <span>Estado:</span>
                                <span class="flex items-center space-x-1">
                                    <CheckCircle class="h-4 w-4" />
                                    <span>Pagado</span>
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment Info -->
                    <Card v-if="invoice.paid_amount > 0">
                        <CardHeader>
                            <CardTitle>Información de Pago</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="invoice.paid_date">
                                <Label class="text-sm font-medium text-muted-foreground">Fecha de Pago</Label>
                                <p>{{ formatDate(invoice.paid_date) }}</p>
                            </div>
                            <div v-if="invoice.payment_method">
                                <Label class="text-sm font-medium text-muted-foreground">Método de Pago</Label>
                                <p>{{ invoice.payment_method }}</p>
                            </div>
                            <div v-if="invoice.payment_reference">
                                <Label class="text-sm font-medium text-muted-foreground">Referencia</Label>
                                <p class="font-mono text-sm">{{ invoice.payment_reference }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment History -->
                    <Card v-if="invoice.payment_applications && invoice.payment_applications.length > 0">
                        <CardHeader>
                            <CardTitle>Historial de Pagos</CardTitle>
                            <CardDescription>
                                Pagos aplicados a esta factura ({{
                                    invoice.payment_applications.filter((app) => app.status === 'active').length
                                }}
                                activos)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Resumen de Aplicaciones de Pago -->
                                <div class="grid grid-cols-2 gap-4 rounded-lg bg-muted p-4">
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">Total Aplicado</p>
                                        <p class="text-xl font-bold text-green-600">
                                            {{
                                                formatCurrency(
                                                    invoice.payment_applications
                                                        .filter((app) => app.status === 'activo')
                                                        .reduce((sum, app) => sum + app.amount_applied, 0),
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">Aplicaciones</p>
                                        <p class="text-xl font-bold">
                                            {{ invoice.payment_applications.filter((app) => app.status === 'activo').length }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Tabla de Aplicaciones de Pago -->
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Pago</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead class="text-right">Monto</TableHead>
                                                <TableHead>Método</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead class="text-center">Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow
                                                v-for="application in invoice.payment_applications"
                                                :key="application.id"
                                                class="hover:bg-muted/50"
                                            >
                                                <TableCell>
                                                    <div class="space-y-1">
                                                        <div class="font-mono text-sm font-medium">
                                                            {{ application.payment.payment_number }}
                                                        </div>
                                                        <div class="text-xs text-muted-foreground">
                                                            Total: {{ formatCurrency(application.payment.total_amount) }}
                                                        </div>
                                                        <div v-if="application.payment.reference_number" class="text-xs text-muted-foreground">
                                                            Ref: {{ application.payment.reference_number }}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="text-sm">
                                                        {{
                                                            new Date(application.applied_date).toLocaleDateString('es-CO', {
                                                                year: 'numeric',
                                                                month: 'short',
                                                                day: 'numeric',
                                                            })
                                                        }}
                                                    </div>
                                                    <div class="text-xs text-muted-foreground">
                                                        Pago:
                                                        {{
                                                            new Date(application.payment.payment_date).toLocaleDateString('es-CO', {
                                                                year: 'numeric',
                                                                month: 'short',
                                                                day: 'numeric',
                                                            })
                                                        }}
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <div
                                                        :class="[
                                                            'font-mono text-sm font-medium',
                                                            application.status === 'activo' ? 'text-green-600' : 'text-red-600 line-through',
                                                        ]"
                                                    >
                                                        {{ formatCurrency(application.amount_applied) }}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="flex items-center gap-2">
                                                        <CreditCard class="h-4 w-4" />
                                                        <span class="text-sm">{{ application.payment.payment_method_label }}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        :class="
                                                            application.status === 'activo'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        "
                                                    >
                                                        {{ application.status_label }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell class="text-center">
                                                    <Link
                                                        :href="`/finance/payments/${application.payment.id}`"
                                                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                                    >
                                                        <ExternalLink class="h-3 w-3" />
                                                        Ver
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Created By Info -->
                                <div v-if="invoice.payment_applications.length > 0" class="text-xs text-muted-foreground">
                                    <div v-for="application in invoice.payment_applications.slice(0, 1)" :key="application.id">
                                        Aplicado por: {{ application.payment.created_by?.name || 'Sistema' }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment Action -->
                    <Card v-if="canReceivePayment">
                        <CardHeader>
                            <CardTitle>Registrar Pago</CardTitle>
                            <CardDescription> Registra un pago para esta factura </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild class="w-full" >
                                <Link :href="`/finance/payments/create?apartment_id=${invoice.apartment.id}`">
                                    <CreditCard class="mr-2 h-4 w-4" />
                                    Registrar Pago
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
