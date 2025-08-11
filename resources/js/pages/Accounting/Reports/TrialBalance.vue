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
    
    if (props.report.accounts && Array.isArray(props.report.accounts)) {
        props.report.accounts.forEach(account => {
            if (groups[account.account_type]) {
                groups[account.account_type].accounts.push(account);
            }
        });
    }
    
    // Sort accounts within each group by code
    Object.values(groups).forEach(group => {
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
                    <p class="text-muted-foreground">
                        Saldos de todas las cuentas al {{ formatDate(report.as_of_date) }}
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
                    <CardTitle class="text-lg flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Filtros de Fecha
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="as_of_date">Fecha de Corte</Label>
                            <Input
                                id="as_of_date"
                                type="date"
                                v-model="form.as_of_date"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showFilters = false">
                            Cancelar
                        </Button>
                        <Button @click="applyFilters">
                            Aplicar Filtros
                        </Button>
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
                        <p class="text-xs text-muted-foreground mt-1">
                            Suma de saldos deudores
                        </p>
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
                        <p class="text-xs text-muted-foreground mt-1">
                            Suma de saldos acreedores
                        </p>
                    </CardContent>
                </Card>
                
                <Card :class="report.is_balanced ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium flex items-center gap-2">
                            <Scale class="h-4 w-4" />
                            Estado del Balance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.is_balanced ? 'text-green-600' : 'text-red-600'">
                            {{ report.is_balanced ? '✓ Balanceado' : '✗ Desbalanceado' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
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
                        <p class="font-medium">
                            Advertencia: El balance de prueba no está balanceado. 
                            Diferencia: {{ formatCurrency(difference) }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Trial Balance by Account Type -->
            <div class="space-y-6">
                <div v-for="(group, type) in visibleGroups" :key="type">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-xl flex items-center justify-between" :class="group.color">
                                <span>{{ group.name }}</span>
                                <div class="text-sm font-normal text-muted-foreground">
                                    {{ group.accounts.length }} cuenta{{ group.accounts.length !== 1 ? 's' : '' }}
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-24">Código</TableHead>
                                        <TableHead>Nombre de la Cuenta</TableHead>
                                        <TableHead class="text-right">Saldo Deudor</TableHead>
                                        <TableHead class="text-right">Saldo Acreedor</TableHead>
                                        <TableHead class="text-right">Saldo</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="account in group.accounts" :key="account.code">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono font-medium">
                                            {{ formatCurrency(Math.abs(account.balance)) }}
                                        </TableCell>
                                    </TableRow>
                                    <!-- Subtotal for this account type -->
                                    <TableRow class="border-t-2 bg-muted/50">
                                        <TableCell colspan="2" class="font-bold" :class="group.color">
                                            Subtotal {{ group.name }}
                                        </TableCell>
                                        <TableCell class="text-right font-bold font-mono" :class="group.color">
                                            {{ formatCurrency(accountTypeTotals[type].debits) }}
                                        </TableCell>
                                        <TableCell class="text-right font-bold font-mono" :class="group.color">
                                            {{ formatCurrency(accountTypeTotals[type].credits) }}
                                        </TableCell>
                                        <TableCell class="text-right font-bold font-mono" :class="group.color">
                                            {{ formatCurrency(accountTypeTotals[type].debits + accountTypeTotals[type].credits) }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Grand Totals -->
            <Card class="border-2" :class="report.is_balanced ? 'border-green-300' : 'border-red-300'">
                <CardHeader>
                    <CardTitle class="text-xl">Totales Generales</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableBody>
                            <TableRow class="text-lg font-bold">
                                <TableCell>TOTALES</TableCell>
                                <TableCell class="text-right font-mono text-blue-600">
                                    {{ formatCurrency(report.total_debits) }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-green-600">
                                    {{ formatCurrency(report.total_credits) }}
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatCurrency(report.total_debits + report.total_credits) }}
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!report.is_balanced" class="text-red-600 font-semibold">
                                <TableCell>DIFERENCIA</TableCell>
                                <TableCell colspan="3" class="text-right font-mono">
                                    {{ formatCurrency(difference) }}
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
                            <div v-for="(group, type) in visibleGroups" :key="type"
                                 class="flex justify-between items-center p-2 rounded bg-muted/50">
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