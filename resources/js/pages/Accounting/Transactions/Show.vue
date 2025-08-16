<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle, DollarSign, Edit, Hash, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface TransactionEntry {
    id: number;
    account: {
        id: number;
        code: string;
        name: string;
        account_type: string;
    };
    description: string;
    debit_amount: number | string;
    credit_amount: number | string;
}

interface AccountingTransaction {
    id: number;
    reference: string;
    description: string;
    transaction_date: string;
    total_amount: number | string;
    status: 'borrador' | 'contabilizado' | 'cancelado';
    created_by: {
        id: number;
        name: string;
        email: string;
    };
    posted_by?: {
        id: number;
        name: string;
        email: string;
    };
    posted_at?: string;
    entries: TransactionEntry[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    transaction: AccountingTransaction;
}>();

// Computed properties
const statusInfo = computed(() => {
    const statusMap = {
        borrador: {
            label: 'Borrador',
            color: 'bg-gray-100 text-gray-800',
            icon: Edit,
            description: 'Transacción en proceso de creación',
        },
        contabilizado: {
            label: 'Contabilizado',
            color: 'bg-green-100 text-green-800',
            icon: CheckCircle,
            description: 'Transacción confirmada y registrada',
        },
        cancelado: {
            label: 'Cancelada',
            color: 'bg-red-100 text-red-800',
            icon: XCircle,
            description: 'Transacción cancelada o anulada',
        },
    };
    return (
        statusMap[props.transaction.status] || {
            label: 'Desconocido',
            color: 'bg-gray-100 text-gray-800',
            icon: AlertCircle,
            description: 'Estado no reconocido',
        }
    );
});

const totalDebits = computed(() => {
    return props.transaction.entries.reduce((sum, entry) => sum + parseFloat(entry.debit_amount || 0), 0);
});

const totalCredits = computed(() => {
    return props.transaction.entries.reduce((sum, entry) => sum + parseFloat(entry.credit_amount || 0), 0);
});

const isBalanced = computed(() => {
    return Math.abs(totalDebits.value - totalCredits.value) < 0.01;
});

const canEdit = computed(() => {
    return props.transaction.status === 'borrador';
});

const referenceDisplay = computed(() => {
    if (!props.transaction.reference_type) {
        return props.transaction.transaction_number || 'Manual';
    }

    const reference = props.transaction.reference;
    const referenceType = props.transaction.reference_type;

    if (referenceType === 'invoice' && reference?.invoice_number) {
        return reference.invoice_number;
    } else if (referenceType === 'payment' && reference?.payment_number) {
        return reference.payment_number;
    } else if (referenceType === 'payment_application' && reference) {
        // For payment applications, show payment number
        const payment = reference.payment || {};
        return payment.payment_number || `Aplicación de Pago #${reference.id}`;
    } else if (referenceType === 'payment_application_reversal' && reference) {
        return `Reverso Aplicación de Pago #${reference.id}`;
    } else if (reference?.id) {
        const typeLabels = {
            payment_application: 'Aplicación de Pago',
            payment_application_reversal: 'Reverso Aplicación de Pago',
            invoice: 'Factura',
            payment: 'Pago',
            budget: 'Presupuesto',
            expense: 'Gasto',
        };
        const typeLabel = typeLabels[referenceType] || referenceType.charAt(0).toUpperCase() + referenceType.slice(1);
        return `${typeLabel} #${reference.id}`;
    } else {
        const typeLabels = {
            payment_application: 'Aplicación de Pago',
            payment_application_reversal: 'Reverso Aplicación de Pago',
            invoice: 'Factura',
            payment: 'Pago',
            budget: 'Presupuesto',
            expense: 'Gasto',
        };
        const typeLabel = typeLabels[referenceType] || referenceType.charAt(0).toUpperCase() + referenceType.slice(1);
        return props.transaction.transaction_number || typeLabel;
    }
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

const printTransaction = () => {
    window.print();
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Transacciones', href: '/accounting/transactions' },
    { title: referenceDisplay.value, href: `/accounting/transactions/${props.transaction.id}` },
];
</script>

<template>
    <Head :title="referenceDisplay" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ referenceDisplay }}</h1>
                        <Badge :class="statusInfo.color">
                            <component :is="statusInfo.icon" class="mr-1 h-3 w-3" />
                            {{ statusInfo.label }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">{{ transaction.description }}</p>
                </div>
                <div class="no-print flex items-center gap-3">
                    <Link href="/accounting/transactions">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link v-if="canEdit" :href="`/accounting/transactions/${transaction.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Transaction Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Transacción</CardTitle>
                            <CardDescription>Información básica y estado actual</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Referencia</Label>
                                    <p class="font-mono text-lg">{{ referenceDisplay }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Fecha de Transacción</Label>
                                    <p class="text-lg">{{ formatDate(transaction.transaction_date) }}</p>
                                </div>
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Descripción</Label>
                                <p class="text-sm leading-relaxed">{{ transaction.description }}</p>
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
                                    <Label class="text-sm font-medium text-muted-foreground">Balance</Label>
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="isBalanced ? CheckCircle : AlertCircle"
                                            :class="['h-4 w-4', isBalanced ? 'text-green-600' : 'text-red-600']"
                                        />
                                        <span :class="isBalanced ? 'text-green-600' : 'text-red-600'">
                                            {{ isBalanced ? 'Balanceado' : 'No Balanceado' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <Label class="text-muted-foreground">Creada por</Label>
                                    <p>{{ transaction.created_by?.name || 'Sistema' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDateTime(transaction.created_at) }}</p>
                                </div>
                                <div v-if="transaction.posted_by && transaction.posted_at">
                                    <Label class="text-muted-foreground">Contabilizada por</Label>
                                    <p>{{ transaction.posted_by?.name || 'Sistema' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDateTime(transaction.posted_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Transaction Entries -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Asientos Contables</CardTitle>
                            <CardDescription>Detalle de débitos y créditos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Balance Summary -->
                            <div class="mb-6 grid grid-cols-3 gap-4 rounded-lg bg-muted p-4">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Total Débitos</p>
                                    <p class="text-xl font-bold text-green-600">
                                        {{ formatCurrency(totalDebits) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Total Créditos</p>
                                    <p class="text-xl font-bold text-red-600">
                                        {{ formatCurrency(totalCredits) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Diferencia</p>
                                    <p :class="['text-xl font-bold', isBalanced ? 'text-green-600' : 'text-red-600']">
                                        {{ formatCurrency(Math.abs(totalDebits - totalCredits)) }}
                                    </p>
                                    <p v-if="!isBalanced" class="text-xs text-red-600">No balanceado</p>
                                    <p v-else class="text-xs text-green-600">✓ Balanceado</p>
                                </div>
                            </div>

                            <!-- Entries Table -->
                            <div class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Cuenta</TableHead>
                                            <TableHead>Descripción</TableHead>
                                            <TableHead class="text-right">Débito</TableHead>
                                            <TableHead class="text-right">Crédito</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="entry in transaction.entries"
                                            :key="entry.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="router.visit(`/accounting/chart-of-accounts/${entry.account.id}`)"
                                        >
                                            <TableCell>
                                                <div class="space-y-1">
                                                    <div class="font-mono text-sm">{{ entry.account.code }}</div>
                                                    <div class="text-sm font-medium">{{ entry.account.name }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ entry.account.account_type }}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="text-sm">{{ entry.description }}</div>
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <div v-if="entry.debit_amount > 0" class="font-mono text-red-600">
                                                    {{ formatCurrency(entry.debit_amount) }}
                                                </div>
                                                <div v-else class="text-muted-foreground">—</div>
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <div v-if="entry.credit_amount > 0" class="font-mono text-green-600">
                                                    {{ formatCurrency(entry.credit_amount) }}
                                                </div>
                                                <div v-else class="text-muted-foreground">—</div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="no-print space-y-6">
                    <!-- Transaction Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Resumen
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground">Monto Total</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(parseFloat(transaction.total_amount || 0)) }}</p>
                            </div>

                            <Separator />

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Número de asientos:</span>
                                    <span>{{ transaction.entries.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Cuentas afectadas:</span>
                                    <span>{{ new Set(transaction.entries.map((e) => e.account.id)).size }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Transaction Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Hash class="h-5 w-5" />
                                Estado y Fechas
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
                                    <Label class="text-sm text-muted-foreground">Fecha de Transacción</Label>
                                    <p class="text-sm">{{ formatDate(transaction.transaction_date) }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm text-muted-foreground">Última Modificación</Label>
                                    <p class="text-sm">{{ formatDateTime(transaction.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <!-- <Card>
                        <CardHeader>
                            <CardTitle>Acciones Rápidas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 gao-2">
                            <Button
                                v-if="canEdit"
                                variant="outline"
                                class="w-full justify-start gap-2"
                                @click="router.visit(`/accounting/transactions/${transaction.id}/edit`)"
                            >
                                <Edit class="h-4 w-4" />
                                Editar Transacción
                            </Button>

                            <Link :href="`/accounting/transactions/${transaction.id}/duplicate`">
                                <Button
                                    variant="outline"
                                    class="w-full justify-start gap-2"
                                >
                                    <FileText class="h-4 w-4" />
                                    Duplicar Transacción
                                </Button>
                            </Link>

                            <Button variant="outline" class="w-full justify-start gap-2 mt-3" @click="printTransaction">
                                <FileText class="h-4 w-4" />
                                Imprimir Comprobante
                            </Button>
                        </CardContent>
                    </Card> -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Hide non-essential elements when printing */
    .no-print,
    .no-print *,
    nav,
    .breadcrumbs {
        display: none !important;
        visibility: hidden !important;
    }

    /* Reset body and page setup */
    body {
        font-size: 12pt !important;
        line-height: 1.4 !important;
        color: black !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Optimize layout for printing */
    .container {
        max-width: none !important;
        width: 100% !important;
        padding: 20px !important;
        margin: 0 !important;
    }

    /* Force single column layout */
    .grid.grid-cols-1.gap-8.lg\\:grid-cols-3 {
        display: block !important;
        grid-template-columns: none !important;
    }

    .space-y-6.lg\\:col-span-2 {
        display: block !important;
        width: 100% !important;
    }

    /* Ensure all cards and content are visible */
    .space-y-6,
    .card,
    [class*='Card'] {
        display: block !important;
        margin-bottom: 20px !important;
        page-break-inside: avoid;
    }

    /* Style tables for print */
    table {
        border-collapse: collapse !important;
        width: 100% !important;
        margin: 10px 0 !important;
    }

    th,
    td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        text-align: left !important;
        font-size: 11pt !important;
    }

    th {
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
    }

    /* Hide interactive elements */
    button,
    .cursor-pointer {
        display: none !important;
    }

    /* Ensure text content is visible */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    div,
    label {
        color: black !important;
        background: transparent !important;
    }

    /* Style badges */
    .badge,
    [class*='bg-gray'],
    [class*='bg-green'],
    [class*='bg-red'] {
        border: 1px solid #000 !important;
        padding: 2px 6px !important;
        background: #f5f5f5 !important;
        color: black !important;
    }

    /* Page breaks */
    .transaction-details,
    .transaction-entries {
        page-break-inside: avoid;
    }
}
</style>
