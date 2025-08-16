<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { BookOpen, Calendar, Download, Filter, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
    nature: string;
}

interface LedgerEntry {
    date: string;
    transaction_number: string;
    description: string;
    debit_amount: number;
    credit_amount: number;
    balance: number;
}

interface Period {
    start_date: string;
    end_date: string;
}

interface Report {
    account: Account;
    period: Period;
    opening_balance: number;
    closing_balance: number;
    entries: LedgerEntry[];
    total_debits: number;
    total_credits: number;
}

interface Filters {
    account_id: number;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    report: Report;
    filters: Filters;
    accounts: Account[];
}>();

const showFilters = ref(false);

const form = useForm({
    account_id: props.filters.account_id,
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Contabilidad',
        href: '/accounting',
    },
    {
        title: 'Reportes',
        href: '/accounting/reports',
    },
    {
        title: 'Libro Mayor',
        href: '/accounting/reports/general-ledger',
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const applyFilters = () => {
    form.get('/accounting/reports/general-ledger', {
        preserveState: true,
    });
};

const exportReport = () => {
    const params = new URLSearchParams({
        account_id: props.filters.account_id.toString(),
        start_date: props.filters.start_date,
        end_date: props.filters.end_date,
    });
    window.location.href = `/accounting/reports/general-ledger/export?${params.toString()}`;
};

const netChange = computed(() => {
    return props.report.closing_balance - props.report.opening_balance;
});

const movementSummary = computed(() => {
    return {
        totalMovements: props.report.entries.length,
        increasingMovements: props.report.entries.filter((entry) => {
            const movement =
                props.report.account.nature === 'debit' ? entry.debit_amount - entry.credit_amount : entry.credit_amount - entry.debit_amount;
            return movement > 0;
        }).length,
        decreasingMovements: props.report.entries.filter((entry) => {
            const movement =
                props.report.account.nature === 'debit' ? entry.debit_amount - entry.credit_amount : entry.credit_amount - entry.debit_amount;
            return movement < 0;
        }).length,
    };
});

const getMovementIcon = (entry: LedgerEntry) => {
    const movement = props.report.account.nature === 'debit' ? entry.debit_amount - entry.credit_amount : entry.credit_amount - entry.debit_amount;
    return movement > 0 ? TrendingUp : TrendingDown;
};

const getMovementColor = (entry: LedgerEntry) => {
    const movement = props.report.account.nature === 'debit' ? entry.debit_amount - entry.credit_amount : entry.credit_amount - entry.debit_amount;
    return movement > 0 ? 'text-green-600' : 'text-red-600';
};

const isDebitAccount = computed(() => props.report.account.nature === 'debit');
</script>

<template>
    <Head title="Libro Mayor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Libro Mayor</h1>
                    <p class="text-muted-foreground">{{ report.account.code }} - {{ report.account.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        Del {{ formatDate(report.period.start_date) }} al {{ formatDate(report.period.end_date) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="showFilters = !showFilters">
                        <Filter class="mr-2 h-4 w-4" />
                        Filtros
                    </Button>
                    <Button variant="outline" @click="exportReport">
                        <Download class="mr-2 h-4 w-4" />
                        Exportar
                    </Button>
                </div>
            </div>

            <!-- Filters Panel -->
            <Card v-if="showFilters" class="border-dashed">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <Calendar class="h-5 w-5" />
                        Filtros del Libro Mayor
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="account_id">Cuenta Contable</Label>
                            <Select v-model="form.account_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cuenta" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="account in accounts" :key="account.id" :value="account.id.toString()">
                                        {{ account.code }} - {{ account.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="start_date">Fecha Inicial</Label>
                            <Input id="start_date" type="date" v-model="form.start_date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">Fecha Final</Label>
                            <Input id="end_date" type="date" v-model="form.end_date" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showFilters = false"> Cancelar </Button>
                        <Button @click="applyFilters"> Aplicar Filtros </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Account Summary -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Saldo Inicial</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.opening_balance >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(Math.abs(report.opening_balance)) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ report.opening_balance >= 0 ? (isDebitAccount ? 'Deudor' : 'Acreedor') : isDebitAccount ? 'Acreedor' : 'Deudor' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Saldo Final</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.closing_balance >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(Math.abs(report.closing_balance)) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ report.closing_balance >= 0 ? (isDebitAccount ? 'Deudor' : 'Acreedor') : isDebitAccount ? 'Acreedor' : 'Deudor' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Cambio Neto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2 text-2xl font-bold" :class="netChange >= 0 ? 'text-green-600' : 'text-red-600'">
                            <component :is="netChange >= 0 ? TrendingUp : TrendingDown" class="h-5 w-5" />
                            {{ formatCurrency(Math.abs(netChange)) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ netChange >= 0 ? 'Aumento' : 'Disminución' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Movimientos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ movementSummary.totalMovements }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ movementSummary.increasingMovements }} ↑ / {{ movementSummary.decreasingMovements }} ↓
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Ledger Entries -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <BookOpen class="h-5 w-5" />
                        Movimientos Contables
                        <span class="ml-2 text-sm font-normal text-muted-foreground"> ({{ report.entries.length }} registros) </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="report.entries.length === 0" class="py-8 text-center text-muted-foreground">
                        <BookOpen class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">No hay movimientos en el período seleccionado</p>
                        <p class="text-sm">Selecciona un período diferente o verifica que la cuenta tenga transacciones</p>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Opening Balance Row -->
                        <Table>
                            <TableBody>
                                <TableRow class="border-2 border-dashed bg-muted/30">
                                    <TableCell class="font-bold">{{ formatDate(report.period.start_date) }}</TableCell>
                                    <TableCell class="font-bold">SALDO INICIAL</TableCell>
                                    <TableCell>Saldo anterior al período</TableCell>
                                    <TableCell class="text-right">-</TableCell>
                                    <TableCell class="text-right">-</TableCell>
                                    <TableCell class="text-right font-mono font-bold">
                                        {{ formatCurrency(Math.abs(report.opening_balance)) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Detailed Movements -->
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-28">Fecha</TableHead>
                                    <TableHead class="w-32">Comprobante</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead class="w-32 text-right">Débito</TableHead>
                                    <TableHead class="w-32 text-right">Crédito</TableHead>
                                    <TableHead class="w-32 text-right">Saldo</TableHead>
                                    <TableHead class="w-8"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(entry, index) in report.entries" :key="index" class="hover:bg-muted/50">
                                    <TableCell class="font-mono text-sm">
                                        {{ formatDate(entry.date) }}
                                    </TableCell>
                                    <TableCell class="font-mono text-sm font-medium">
                                        {{ entry.transaction_number }}
                                    </TableCell>
                                    <TableCell class="max-w-xs">
                                        <div class="truncate" :title="entry.description">
                                            {{ entry.description }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ entry.debit_amount > 0 ? formatCurrency(entry.debit_amount) : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ entry.credit_amount > 0 ? formatCurrency(entry.credit_amount) : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-medium">
                                        {{ formatCurrency(Math.abs(entry.balance)) }}
                                    </TableCell>
                                    <TableCell>
                                        <component :is="getMovementIcon(entry)" :class="getMovementColor(entry)" class="h-4 w-4" />
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Summary Totals -->
                        <Table>
                            <TableBody>
                                <TableRow class="border-t-2 bg-primary/5 font-semibold">
                                    <TableCell colspan="3" class="font-bold">TOTALES DEL PERÍODO</TableCell>
                                    <TableCell class="text-right font-mono font-bold text-blue-600">
                                        {{ formatCurrency(report.total_debits) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-green-600">
                                        {{ formatCurrency(report.total_credits) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold">
                                        {{ formatCurrency(Math.abs(report.closing_balance)) }}
                                    </TableCell>
                                    <TableCell></TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Account Information -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Información de la Cuenta</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Código:</span>
                                <span class="font-mono font-medium">{{ report.account.code }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Nombre:</span>
                                <span class="font-medium">{{ report.account.name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Naturaleza:</span>
                                <span class="font-medium capitalize">
                                    {{ report.account.nature === 'debit' ? 'Deudora' : 'Acreedora' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Resumen del Período</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Período:</span>
                                <span class="font-medium">
                                    {{ formatDate(report.period.start_date) }} - {{ formatDate(report.period.end_date) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total movimientos:</span>
                                <span class="font-medium">{{ report.entries.length }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total débitos:</span>
                                <span class="font-mono font-medium text-blue-600">
                                    {{ formatCurrency(report.total_debits) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total créditos:</span>
                                <span class="font-mono font-medium text-green-600">
                                    {{ formatCurrency(report.total_credits) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
