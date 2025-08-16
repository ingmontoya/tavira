<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    invoice: {
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
        notes?: string;
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
    };
    resident: {
        full_name: string;
        apartment: {
            full_address: string;
            tower: string;
            number: string;
        };
    };
}>();

const breadcrumbs = [
    { label: 'Estado de Cuenta', href: route('account-statement.index'), current: false },
    { label: `Factura ${props.invoice.invoice_number}`, href: '#', current: true },
];

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
        month: 'long',
        day: 'numeric',
    });
};

const printInvoice = () => {
    window.print();
};

const downloadInvoice = () => {
    // TODO: Implement PDF download functionality
    console.log('Download invoice functionality to be implemented');
};
</script>

<template>
    <Head :title="`Factura ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <Link :href="route('account-statement.index')" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                    <Icon name="arrow-left" class="mr-2 h-4 w-4" />
                    Volver al Estado de Cuenta
                </Link>

                <div class="flex space-x-3">
                    <Button @click="printInvoice" variant="outline" size="sm">
                        <Icon name="printer" class="mr-2 h-4 w-4" />
                        Imprimir
                    </Button>
                    <Button @click="downloadInvoice" variant="outline" size="sm">
                        <Icon name="download" class="mr-2 h-4 w-4" />
                        Descargar PDF
                    </Button>
                </div>
            </div>

            <!-- Invoice Content -->
            <Card class="print:shadow-none">
                <div class="space-y-8 p-8">
                    <!-- Invoice Header -->
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-gray-900">FACTURA</h1>
                        <p class="mt-2 text-xl font-semibold">{{ invoice.invoice_number }}</p>
                        <Badge :class="invoice.status_badge.class" class="mt-2">
                            {{ invoice.status_badge.text }}
                        </Badge>
                    </div>

                    <!-- Invoice Details & Resident Info -->
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Invoice Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold">Información de la Factura</h3>
                            <div class="space-y-2 text-sm">
                                <div class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Número:</span>
                                    <span>{{ invoice.invoice_number }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Tipo:</span>
                                    <span>{{ invoice.type_label }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Período:</span>
                                    <span>{{ invoice.billing_period_label }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Fecha Emisión:</span>
                                    <span>{{ formatDate(invoice.billing_date) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Fecha Vencimiento:</span>
                                    <span class="font-semibold" :class="invoice.days_overdue > 0 ? 'text-red-600' : 'text-gray-900'">
                                        {{ formatDate(invoice.due_date) }}
                                    </span>
                                </div>
                                <div v-if="invoice.days_overdue > 0" class="grid grid-cols-2 gap-4">
                                    <span class="font-medium">Días de Mora:</span>
                                    <span class="font-semibold text-red-600">{{ invoice.days_overdue }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Resident Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold">Información del Residente</h3>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="font-medium">Nombre:</span>
                                    <p class="mt-1">{{ resident.full_name }}</p>
                                </div>
                                <div>
                                    <span class="font-medium">Apartamento:</span>
                                    <p class="mt-1">{{ resident.apartment.full_address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Invoice Items -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Detalle de la Factura</h3>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Concepto</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead class="text-right">Cantidad</TableHead>
                                    <TableHead class="text-right">Valor Unitario</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in invoice.items" :key="index">
                                    <TableCell class="font-medium">{{ item.concept_name }}</TableCell>
                                    <TableCell>{{ item.description || '-' }}</TableCell>
                                    <TableCell class="text-right">{{ item.quantity }}</TableCell>
                                    <TableCell class="text-right">{{ formatCurrency(item.unit_price) }}</TableCell>
                                    <TableCell class="text-right font-semibold">{{ formatCurrency(item.total_price) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <Separator />

                    <!-- Invoice Totals -->
                    <div class="flex justify-end">
                        <div class="w-full max-w-sm space-y-3">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span class="font-medium">{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>

                            <div v-if="invoice.early_discount > 0" class="flex justify-between text-green-600">
                                <span>Descuento por pronto pago:</span>
                                <span class="font-medium">-{{ formatCurrency(invoice.early_discount) }}</span>
                            </div>

                            <div v-if="invoice.late_fees > 0" class="flex justify-between text-red-600">
                                <span>Intereses de mora:</span>
                                <span class="font-medium">+{{ formatCurrency(invoice.late_fees) }}</span>
                            </div>

                            <Separator />

                            <div class="flex justify-between text-lg font-bold">
                                <span>Total a Pagar:</span>
                                <span>{{ formatCurrency(invoice.total_amount) }}</span>
                            </div>

                            <div v-if="invoice.paid_amount > 0" class="flex justify-between text-green-600">
                                <span>Total Pagado:</span>
                                <span class="font-medium">{{ formatCurrency(invoice.paid_amount) }}</span>
                            </div>

                            <div v-if="invoice.balance_amount > 0" class="flex justify-between text-lg font-bold text-red-600">
                                <span>Saldo Pendiente:</span>
                                <span>{{ formatCurrency(invoice.balance_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div v-if="invoice.payments && invoice.payments.length > 0" class="space-y-4">
                        <Separator />
                        <h3 class="text-lg font-semibold">Historial de Pagos</h3>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Número de Pago</TableHead>
                                    <TableHead>Fecha de Pago</TableHead>
                                    <TableHead>Método</TableHead>
                                    <TableHead>Fecha Aplicación</TableHead>
                                    <TableHead class="text-right">Monto Aplicado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(payment, index) in invoice.payments" :key="index">
                                    <TableCell class="font-medium">{{ payment.payment_number }}</TableCell>
                                    <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                                    <TableCell>{{ payment.payment_method }}</TableCell>
                                    <TableCell>{{ formatDate(payment.applied_date) }}</TableCell>
                                    <TableCell class="text-right font-semibold">{{ formatCurrency(payment.amount_applied) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Notes -->
                    <div v-if="invoice.notes" class="space-y-2">
                        <Separator />
                        <h3 class="text-lg font-semibold">Observaciones</h3>
                        <p class="text-sm text-gray-600">{{ invoice.notes }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="text-center text-sm text-gray-500 print:text-gray-700">
                        <p>Este documento es una representación impresa de una factura electrónica.</p>
                        <p class="mt-1">Generado el {{ formatDate(new Date().toISOString()) }}</p>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .print\\:shadow-none,
    .print\\:shadow-none * {
        visibility: visible;
    }

    .print\\:shadow-none {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    @page {
        margin: 1cm;
    }
}
</style>
