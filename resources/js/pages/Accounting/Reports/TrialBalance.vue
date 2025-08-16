<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Download, Filter, Scale } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Account {
    code: string;
    name: string;
    account_type: string;
    nature: string;
    debit_balance: number;
    credit_balance: number;
    balance: number;
}

interface Report {
    as_of_date: string;
    accounts: Account[];
    total_debits: number;
    total_credits: number;
    is_balanced: boolean;
}

interface Filters {
    as_of_date: string;
}

const props = defineProps<{
    report: Report;
    filters: Filters;
}>();

const showFilters = ref(false);

const form = useForm({
    as_of_date: props.filters.as_of_date,
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
        title: 'Balance de Prueba',
        href: '/accounting/reports/trial-balance',
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
        month: 'long',
        day: 'numeric',
    });
};

const applyFilters = () => {
    form.get('/accounting/reports/trial-balance', {
        preserveState: true,
    });
};

const exportReport = () => {
    window.location.href = `/accounting/reports/trial-balance/export?as_of_date=${props.filters.as_of_date}`;
};

// Group accounts by account type for better organization
const groupedAccounts = computed(() => {
    const groups = {
        asset: { name: 'Activos', accounts: [], color: 'text-blue-700' },
        liability: { name: 'Pasivos', accounts: [], color: 'text-red-700' },
        equity: { name: 'Patrimonio', accounts: [], color: 'text-purple-700' },
        income: { name: 'Ingresos', accounts: [], color: 'text-green-700' },
        expense: { name: 'Gastos', accounts: [], color: 'text-orange-700' },
    };

    // Convert object to array if needed
    let accounts = props.report.accounts;
    if (accounts && !Array.isArray(accounts)) {
        accounts = Object.values(accounts);
    }

    if (accounts && Array.isArray(accounts)) {
        accounts.forEach((account) => {
            if (groups[account.account_type]) {
                groups[account.account_type].accounts.push(account);
            }
        });
    }

    // Sort accounts within each group by code
    Object.values(groups).forEach((group) => {
        group.accounts.sort((a, b) => a.code.localeCompare(b.code));
    });

    return groups;
});

const accountTypeTotals = computed(() => {
    const totals = {};

    Object.entries(groupedAccounts.value).forEach(([type, group]) => {
        totals[type] = {
            debits: group.accounts.reduce((sum, account) => sum + account.debit_balance, 0),
            credits: group.accounts.reduce((sum, account) => sum + account.credit_balance, 0),
        };
    });

    return totals;
});

const difference = computed(() => {
    return Math.abs(props.report.total_debits - props.report.total_credits);
});

// Filter groups that have accounts
const visibleGroups = computed(() => {
    const filtered = {};
    Object.entries(groupedAccounts.value).forEach(([type, group]) => {
        if (group.accounts.length > 0) {
            filtered[type] = group;
        }
    });
    return filtered;
});
</script>

<template>
    <Head title="Balance de Prueba" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Balance de Prueba</h1>
                    <p class="text-muted-foreground">Saldos de todas las cuentas al {{ formatDate(report.as_of_date) }}</p>
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
                        Filtros de Fecha
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="as_of_date">Fecha de Corte</Label>
                            <Input id="as_of_date" type="date" v-model="form.as_of_date" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showFilters = false"> Cancelar </Button>
                        <Button @click="applyFilters"> Aplicar Filtros </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Balance Status -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total Débitos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ formatCurrency(report.total_debits) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Suma de saldos deudores</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Total Créditos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatCurrency(report.total_credits) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Suma de saldos acreedores</p>
                    </CardContent>
                </Card>

                <Card :class="report.is_balanced ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Scale class="h-4 w-4" />
                            Estado del Balance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.is_balanced ? 'text-green-600' : 'text-red-600'">
                            {{ report.is_balanced ? '✓ Balanceado' : '✗ Desbalanceado' }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ report.is_balanced ? 'Débitos = Créditos' : `Diferencia: ${formatCurrency(difference)}` }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Balance Warning -->
            <Card v-if="!report.is_balanced" class="border-amber-200 bg-amber-50">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-amber-800">
                        <AlertTriangle class="h-5 w-5" />
                        <p class="font-medium">Advertencia: El balance de prueba no está balanceado. Diferencia: {{ formatCurrency(difference) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Traditional Trial Balance Format -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <Scale class="h-5 w-5" />
                        Balance de Prueba Detallado
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">Contabilidad por Causación (Base Devengado) - Al {{ formatDate(report.as_of_date) }}</p>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow class="border-b-2">
                                <TableHead class="w-20">Código</TableHead>
                                <TableHead class="min-w-[300px]">Nombre de la Cuenta</TableHead>
                                <TableHead class="w-32 text-right">Débito (COP)</TableHead>
                                <TableHead class="w-32 text-right">Crédito (COP)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <!-- Assets Section -->
                            <TableRow class="bg-blue-50">
                                <TableCell colspan="4" class="py-3 text-lg font-bold text-blue-700"> ACTIVOS </TableCell>
                            </TableRow>
                            <template v-for="account in groupedAccounts.asset.accounts" :key="account.code">
                                <TableRow class="hover:bg-muted/50">
                                    <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                    <TableCell class="font-medium">{{ account.name }}</TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                    </TableCell>
                                </TableRow>
                            </template>
                            <TableRow class="border-t bg-blue-100">
                                <TableCell colspan="2" class="font-bold text-blue-700"> Subtotal Activos </TableCell>
                                <TableCell class="text-right font-mono font-bold text-blue-700">
                                    {{ formatCurrency(accountTypeTotals.asset?.debits || 0) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold text-blue-700">
                                    {{ formatCurrency(accountTypeTotals.asset?.credits || 0) }}
                                </TableCell>
                            </TableRow>

                            <!-- Liabilities Section -->
                            <template v-if="groupedAccounts.liability.accounts.length > 0">
                                <TableRow class="bg-red-50">
                                    <TableCell colspan="4" class="py-3 text-lg font-bold text-red-700"> PASIVOS </TableCell>
                                </TableRow>
                                <template v-for="account in groupedAccounts.liability.accounts" :key="account.code">
                                    <TableRow class="hover:bg-muted/50">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow class="border-t bg-red-100">
                                    <TableCell colspan="2" class="font-bold text-red-700"> Subtotal Pasivos </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-red-700">
                                        {{ formatCurrency(accountTypeTotals.liability?.debits || 0) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-red-700">
                                        {{ formatCurrency(accountTypeTotals.liability?.credits || 0) }}
                                    </TableCell>
                                </TableRow>
                            </template>

                            <!-- Equity Section -->
                            <template v-if="groupedAccounts.equity.accounts.length > 0">
                                <TableRow class="bg-purple-50">
                                    <TableCell colspan="4" class="py-3 text-lg font-bold text-purple-700"> PATRIMONIO </TableCell>
                                </TableRow>
                                <template v-for="account in groupedAccounts.equity.accounts" :key="account.code">
                                    <TableRow class="hover:bg-muted/50">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow class="border-t bg-purple-100">
                                    <TableCell colspan="2" class="font-bold text-purple-700"> Subtotal Patrimonio </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-purple-700">
                                        {{ formatCurrency(accountTypeTotals.equity?.debits || 0) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-purple-700">
                                        {{ formatCurrency(accountTypeTotals.equity?.credits || 0) }}
                                    </TableCell>
                                </TableRow>
                            </template>

                            <!-- Income Section -->
                            <template v-if="groupedAccounts.income.accounts.length > 0">
                                <TableRow class="bg-green-50">
                                    <TableCell colspan="4" class="py-3 text-lg font-bold text-green-700"> INGRESOS </TableCell>
                                </TableRow>
                                <template v-for="account in groupedAccounts.income.accounts" :key="account.code">
                                    <TableRow class="hover:bg-muted/50">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow class="border-t bg-green-100">
                                    <TableCell colspan="2" class="font-bold text-green-700"> Subtotal Ingresos </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-green-700">
                                        {{ formatCurrency(accountTypeTotals.income?.debits || 0) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-green-700">
                                        {{ formatCurrency(accountTypeTotals.income?.credits || 0) }}
                                    </TableCell>
                                </TableRow>
                            </template>

                            <!-- Expenses Section -->
                            <template v-if="groupedAccounts.expense.accounts.length > 0">
                                <TableRow class="bg-orange-50">
                                    <TableCell colspan="4" class="py-3 text-lg font-bold text-orange-700"> GASTOS </TableCell>
                                </TableRow>
                                <template v-for="account in groupedAccounts.expense.accounts" :key="account.code">
                                    <TableRow class="hover:bg-muted/50">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow class="border-t bg-orange-100">
                                    <TableCell colspan="2" class="font-bold text-orange-700"> Subtotal Gastos </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-orange-700">
                                        {{ formatCurrency(accountTypeTotals.expense?.debits || 0) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono font-bold text-orange-700">
                                        {{ formatCurrency(accountTypeTotals.expense?.credits || 0) }}
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Grand Totals -->
            <Card class="border-2" :class="report.is_balanced ? 'border-green-300' : 'border-red-300'">
                <CardHeader>
                    <CardTitle class="text-xl">Totales Generales</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead></TableHead>
                                <TableHead class="text-right">Saldo Deudor</TableHead>
                                <TableHead class="text-right">Saldo Acreedor</TableHead>
                                <TableHead class="text-right">Diferencia</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow class="border-t-2 text-lg font-bold">
                                <TableCell>TOTALES</TableCell>
                                <TableCell class="text-right font-mono text-blue-600">
                                    {{ formatCurrency(report.total_debits) }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-green-600">
                                    {{ formatCurrency(report.total_credits) }}
                                </TableCell>
                                <TableCell class="text-right font-mono" :class="report.is_balanced ? 'text-green-600' : 'text-red-600'">
                                    {{ formatCurrency(Math.abs(report.total_debits - report.total_credits)) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Resumen por Tipo de Cuenta</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="(group, type) in visibleGroups" :key="type" class="flex items-center justify-between rounded bg-muted/50 p-2">
                                <span class="font-medium" :class="group.color">{{ group.name }}</span>
                                <span class="text-sm text-muted-foreground">{{ group.accounts.length }} cuentas</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Información del Reporte</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Fecha de corte:</span>
                                <span class="font-medium">{{ formatDate(report.as_of_date) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total cuentas:</span>
                                <span class="font-medium">{{ report.accounts.length }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Estado:</span>
                                <span class="font-medium" :class="report.is_balanced ? 'text-green-600' : 'text-red-600'">
                                    {{ report.is_balanced ? 'Balanceado' : 'Desbalanceado' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
