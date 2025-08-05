<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle,
    CreditCard,
    DollarSign,
    Edit,
    FileText,
    Hash,
    Receipt,
    RefreshCw,
    RotateCcw,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface PaymentApplication {
    id: number;
    amount_applied: number | string;
    applied_date: string;
    status: 'active' | 'reversed';
    status_label: string;
    invoice: {
        id: number;
        invoice_number: string;
        total_amount: number | string;
        billing_date: string;
        due_date: string;
        status: string;
        status_label: string;
        items: {
            description: string;
            amount: number;
            concept: string;
        }[];
    };
    created_by: {
        id: number;
        name: string;
    };
}

interface Payment {
    id: number;
    payment_number: string;
    apartment: {
        id: number;
        number: string;
    };
    total_amount: number | string;
    applied_amount: number | string;
    remaining_amount: number | string;
    payment_date: string;
    payment_method: string;
    payment_method_label: string;
    reference_number?: string;
    notes?: string;
    status: 'pendiente' | 'aplicado' | 'parcialmente_aplicado' | 'reversado';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    is_pending: boolean;
    can_be_applied: boolean;
    created_by: {
        id: number;
        name: string;
        email: string;
    };
    applied_by?: {
        id: number;
        name: string;
        email: string;
    };
    applied_at?: string;
    applications: PaymentApplication[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    payment: Payment;
}>();

// Computed properties
const statusInfo = computed(() => {
    const statusMap = {
        pendiente: {
            label: 'Pendiente',
            color: 'bg-yellow-100 text-yellow-800',
            icon: AlertCircle,
            description: 'Pago registrado pero no aplicado a facturas',
        },
        aplicado: {
            label: 'Aplicado',
            color: 'bg-green-100 text-green-800',
            icon: CheckCircle,
            description: 'Pago completamente aplicado a facturas',
        },
        parcialmente_aplicado: {
            label: 'Parcialmente Aplicado',
            color: 'bg-blue-100 text-blue-800',
            icon: RefreshCw,
            description: 'Pago parcialmente aplicado a facturas',
        },
        reversado: {
            label: 'Reversado',
            color: 'bg-red-100 text-red-800',
            icon: XCircle,
            description: 'Aplicaciones de pago reversadas',
        },
    };
    return (
        statusMap[props.payment.status] || {
            label: 'Desconocido',
            color: 'bg-gray-100 text-gray-800',
            icon: AlertCircle,
            description: 'Estado no reconocido',
        }
    );
});

const paymentMethodIcon = computed(() => {
    const iconMap = {
        cash: DollarSign,
        bank_transfer: CreditCard,
        check: FileText,
        credit_card: CreditCard,
        debit_card: CreditCard,
        online: CreditCard,
        other: Receipt,
    };
    return iconMap[props.payment.payment_method] || Receipt;
});

const totalAppliedAmount = computed(() => {
    return props.payment.applications
        .filter((app) => app.status === 'active')
        .reduce((sum, app) => sum + parseFloat((app.amount_applied as string) || '0'), 0);
});

const canEdit = computed(() => {
    return props.payment.is_pending;
});

const canApply = computed(() => {
    return props.payment.can_be_applied;
});

const canReverse = computed(() => {
    return parseFloat((props.payment.applied_amount as string) || '0') > 0;
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const applyPayment = () => {
    router.post(
        `/finance/payments/${props.payment.id}/apply`,
        {},
        {
            preserveScroll: true,
        },
    );
};

const reversePayment = () => {
    if (confirm('¿Está seguro de que desea reversar las aplicaciones de este pago?')) {
        router.post(
            `/finance/payments/${props.payment.id}/reverse`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Finanzas', href: '/finances' },
    { title: 'Pagos', href: '/finance/payments' },
    { title: props.payment.payment_number, href: `/finance/payments/${props.payment.id}` },
];
</script>

<template>
    <Head :title="payment.payment_number" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ payment.payment_number }}</h1>
                        <Badge :class="statusInfo.color">
                            <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                            {{ statusInfo.label }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Apartamento {{ payment.apartment.number }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/finance/payments">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Button v-if="canEdit" class="gap-2" @click="router.visit(`/finance/payments/${payment.id}/edit`)">
                        <Edit class="h-4 w-4" />
                        Editar
                    </Button>
                    <Button v-if="canApply" variant="outline" class="gap-2" @click="applyPayment">
                        <CheckCircle class="h-4 w-4" />
                        Aplicar Pago
                    </Button>
                    <Button v-if="canReverse" variant="destructive" class="gap-2" @click="reversePayment">
                        <RotateCcw class="h-4 w-4" />
                        Reversar
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Payment Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles del Pago</CardTitle>
                            <CardDescription>Información básica y estado actual</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Número de Pago</Label>
                                    <p class="font-mono text-lg">{{ payment.payment_number }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Pago</Label>
                                    <p class="text-lg">{{ formatDate(payment.payment_date) }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Apartamento</Label>
                                    <p class="text-lg font-medium">{{ payment.apartment.number }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Método de Pago</Label>
                                    <div class="flex items-center gap-2">
                                        <component :is="paymentMethodIcon" class="h-4 w-4" />
                                        <span class="text-lg">{{ payment.payment_method_label }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="payment.reference_number">
                                <Label class="text-sm font-medium text-muted-foreground">Número de Referencia</Label>
                                <p class="font-mono text-sm">{{ payment.reference_number }}</p>
                            </div>

                            <div v-if="payment.notes">
                                <Label class="text-sm font-medium text-muted-foreground">Notas</Label>
                                <p class="text-sm leading-relaxed">{{ payment.notes }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Estado</Label>
                                    <div class="flex items-center gap-2">
                                        <Badge :class="statusInfo.color">
                                            <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                                            {{ statusInfo.label }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ statusInfo.description }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Progreso de Aplicación</Label>
                                    <div class="mt-1">
                                        <div class="h-2 rounded-full bg-gray-200">
                                            <div
                                                class="h-2 rounded-full bg-green-500 transition-all duration-300"
                                                :style="{
                                                    width: `${(parseFloat((payment.applied_amount as string) || '0') / parseFloat((payment.total_amount as string) || '1')) * 100}%`,
                                                }"
                                            ></div>
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{
                                                (
                                                    (parseFloat((payment.applied_amount as string) || '0') /
                                                        parseFloat((payment.total_amount as string) || '1')) *
                                                    100
                                                ).toFixed(1)
                                            }}% aplicado
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <Label class="text-muted-foreground">Creado por</Label>
                                    <p>{{ payment.created_by?.name || 'Sistema' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDateTime(payment.created_at) }}</p>
                                </div>
                                <div v-if="payment.applied_by && payment.applied_at">
                                    <Label class="text-muted-foreground">Aplicado por</Label>
                                    <p>{{ payment.applied_by?.name || 'Sistema' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDateTime(payment.applied_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment Applications -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Aplicaciones del Pago</CardTitle>
                            <CardDescription>
                                Facturas a las que se ha aplicado este pago
                                <span v-if="payment.applications.length === 0">(ninguna aplicación aún)</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="payment.applications.length === 0" class="py-8 text-center text-muted-foreground">
                                <Receipt class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                <p class="mb-2 text-lg font-medium">Sin Aplicaciones</p>
                                <p class="text-sm">Este pago aún no se ha aplicado a ninguna factura.</p>
                                <Button v-if="canApply" class="mt-4 gap-2" @click="applyPayment">
                                    <CheckCircle class="h-4 w-4" />
                                    Aplicar Pago Ahora
                                </Button>
                            </div>

                            <div v-else class="space-y-4">
                                <!-- Applications Summary -->
                                <div class="grid grid-cols-3 gap-4 rounded-lg bg-muted p-4">
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">Aplicaciones Activas</p>
                                        <p class="text-xl font-bold">
                                            {{ payment.applications.filter((app) => app.status === 'active').length }}
                                        </p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">Monto Aplicado</p>
                                        <p class="text-xl font-bold text-green-600">
                                            {{ formatCurrency(totalAppliedAmount) }}
                                        </p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">Aplicaciones Reversadas</p>
                                        <p class="text-xl font-bold text-red-600">
                                            {{ payment.applications.filter((app) => app.status === 'reversed').length }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Applications Table -->
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Factura</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead class="text-right">Monto Aplicado</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Aplicado Por</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow
                                                v-for="application in payment.applications"
                                                :key="application.id"
                                                class="cursor-pointer hover:bg-muted/50"
                                                @click="router.visit(`/invoices/${application.invoice.id}`)"
                                            >
                                                <TableCell>
                                                    <div class="space-y-1">
                                                        <div class="font-mono text-sm font-medium">{{ application.invoice.invoice_number }}</div>
                                                        <div class="text-xs text-muted-foreground">
                                                            Total:
                                                            {{ formatCurrency(parseFloat((application.invoice.total_amount as string) || '0')) }}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="text-sm">
                                                        {{ formatDate(application.applied_date) }}
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <div
                                                        :class="[
                                                            'font-mono text-sm font-medium',
                                                            application.status === 'active' ? 'text-green-600' : 'text-red-600 line-through',
                                                        ]"
                                                    >
                                                        {{ formatCurrency(parseFloat((application.amount_applied as string) || '0')) }}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        :class="
                                                            application.status === 'active'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        "
                                                    >
                                                        {{ application.status_label }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="text-sm">{{ application.created_by.name }}</div>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Payment Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Resumen del Pago
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground">Monto Total</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(parseFloat((payment.total_amount as string) || '0')) }}</p>
                            </div>

                            <Separator />

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Monto aplicado:</span>
                                    <span class="font-medium text-green-600">{{
                                        formatCurrency(parseFloat((payment.applied_amount as string) || '0'))
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Monto restante:</span>
                                    <span
                                        :class="[
                                            'font-medium',
                                            parseFloat((payment.remaining_amount as string) || '0') > 0 ? 'text-yellow-600' : 'text-green-600',
                                        ]"
                                    >
                                        {{ formatCurrency(parseFloat((payment.remaining_amount as string) || '0')) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Aplicaciones:</span>
                                    <span>{{ payment.applications.filter((app) => app.status === 'active').length }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Hash class="h-5 w-5" />
                                Información del Pago
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div>
                                    <Label class="text-sm text-muted-foreground">Estado Actual</Label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <component :is="statusInfo.icon" class="h-4 w-4" />
                                        <span class="font-medium">{{ statusInfo.label }}</span>
                                    </div>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Fecha de Pago</Label>
                                    <p class="text-sm">{{ formatDate(payment.payment_date) }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Método de Pago</Label>
                                    <div class="flex items-center gap-2">
                                        <component :is="paymentMethodIcon" class="h-4 w-4" />
                                        <span class="text-sm">{{ payment.payment_method_label }}</span>
                                    </div>
                                </div>

                                <div v-if="payment.reference_number">
                                    <Label class="text-sm text-muted-foreground">Referencia</Label>
                                    <p class="font-mono text-sm">{{ payment.reference_number }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Última Modificación</Label>
                                    <p class="text-sm">{{ formatDateTime(payment.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones Rápidas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button
                                v-if="canEdit"
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/finance/payments/${payment.id}/edit`)"
                            >
                                <Edit class="h-4 w-4" />
                                Editar Pago
                            </Button>

                            <Button v-if="canApply" variant="outline" class="w-full justify-start gap-2" @click="applyPayment">
                                <CheckCircle class="h-4 w-4" />
                                Aplicar a Facturas
                            </Button>

                            <Button
                                v-if="canReverse"
                                variant="outline"
                                class="w-full justify-start gap-2 text-red-600 hover:text-red-700"
                                @click="reversePayment"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Reversar Aplicaciones
                            </Button>

                            <Button variant="outline" class="w-full justify-start gap-2" @click="window.print()">
                                <FileText class="h-4 w-4" />
                                Imprimir Comprobante
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
