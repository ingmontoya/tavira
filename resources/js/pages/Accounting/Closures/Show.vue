<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, TrendingDown, TrendingUp, Undo2, User } from 'lucide-vue-next';

interface Closure {
    id: number;
    fiscal_year: number;
    period_label: string;
    period_start_date: string;
    period_end_date: string;
    closure_date: string;
    status: string;
    status_label: string;
    total_income: number;
    total_expenses: number;
    net_result: number;
    is_profit: boolean;
    closed_by_user: { id: number; name: string; email: string };
    closing_transaction: {
        id: number;
        transaction_number: string;
        transaction_date: string;
        description: string;
        total_debit: number;
        total_credit: number;
        entries: Array<{
            id: number;
            description: string;
            debit_amount: number;
            credit_amount: number;
            account: {
                id: number;
                code: string;
                name: string;
            };
        }>;
    };
    notes?: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    closure: Closure;
    conjunto: any;
}>();

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Cierres Contables', href: route('accounting.closures.index') },
    { title: `Cierre ${props.closure.fiscal_year}`, href: route('accounting.closures.show', props.closure.id) },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'reversed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const reverseClosure = () => {
    if (confirm('¿Está seguro de que desea reversar este cierre contable? Esta acción cancelará todas las transacciones asociadas.')) {
        router.post(route('accounting.closures.reverse', props.closure.id), {}, {
            onSuccess: () => {
                router.visit(route('accounting.closures.index'));
            },
        });
    }
};
</script>

<template>
    <Head :title="`Cierre Contable ${closure.fiscal_year}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ closure.period_label }}</h1>
                        <Badge :variant="getStatusVariant(closure.status)">
                            {{ closure.status_label }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Detalles del cierre contable</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="closure.status === 'completed'"
                        variant="outline"
                        class="gap-2 text-orange-600"
                        @click="reverseClosure"
                    >
                        <Undo2 class="h-4 w-4" />
                        Reversar Cierre
                    </Button>
                    <Link :href="route('accounting.closures.index')">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Summary Cards -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Ingresos del Ejercicio</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-green-600">
                            {{ formatCurrency(closure.total_income) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Gastos del Ejercicio</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-600">
                            {{ formatCurrency(closure.total_expenses) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <component
                                :is="closure.is_profit ? TrendingUp : TrendingDown"
                                class="h-5 w-5"
                                :class="closure.is_profit ? 'text-green-600' : 'text-red-600'"
                            />
                            {{ closure.is_profit ? 'Excedente' : 'Déficit' }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="text-3xl font-bold"
                            :class="closure.is_profit ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ formatCurrency(Math.abs(closure.net_result)) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Closure Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Información del Cierre
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-muted-foreground">Período</p>
                                <p class="font-medium">{{ closure.period_label }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Fecha de Cierre</p>
                                <p class="font-medium">{{ formatDate(closure.closure_date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Fecha Inicio</p>
                                <p class="font-medium">{{ formatDate(closure.period_start_date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Fecha Fin</p>
                                <p class="font-medium">{{ formatDate(closure.period_end_date) }}</p>
                            </div>
                        </div>

                        <div v-if="closure.notes" class="border-t pt-4">
                            <p class="text-sm text-muted-foreground">Notas</p>
                            <p class="mt-1 text-sm">{{ closure.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- User Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Información de Auditoría
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Cerrado Por</p>
                            <p class="font-medium">{{ closure.closed_by_user.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ closure.closed_by_user.email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Fecha de Creación</p>
                            <p class="font-medium">{{ formatDateTime(closure.created_at) }}</p>
                        </div>
                        <div v-if="closure.closing_transaction">
                            <p class="text-sm text-muted-foreground">Transacción de Cierre</p>
                            <p class="font-medium">{{ closure.closing_transaction.transaction_number }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Closing Transaction Details -->
            <Card v-if="closure.closing_transaction" class="mt-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Asiento de Cierre Principal
                    </CardTitle>
                    <CardDescription>
                        {{ closure.closing_transaction.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
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
                                    v-for="entry in closure.closing_transaction.entries"
                                    :key="entry.id"
                                >
                                    <TableCell class="font-mono text-sm">
                                        <div>
                                            <p class="font-medium">{{ entry.account.code }}</p>
                                            <p class="text-xs text-muted-foreground">{{ entry.account.name }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ entry.description }}</TableCell>
                                    <TableCell class="text-right font-mono">
                                        <span v-if="entry.debit_amount > 0" class="text-blue-600">
                                            {{ formatCurrency(entry.debit_amount) }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        <span v-if="entry.credit_amount > 0" class="text-green-600">
                                            {{ formatCurrency(entry.credit_amount) }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                </TableRow>
                                <TableRow class="border-t-2 font-bold">
                                    <TableCell colspan="2" class="text-right">Totales:</TableCell>
                                    <TableCell class="text-right font-mono text-blue-600">
                                        {{ formatCurrency(closure.closing_transaction.total_debit) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono text-green-600">
                                        {{ formatCurrency(closure.closing_transaction.total_credit) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
