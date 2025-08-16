<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Calendar, Download, Filter, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface CashFlowEntry {
    date: string;
    transaction_number: string;
    description: string;
    debit_amount: number;
    credit_amount: number;
}

interface CashAccount {
    account: Account;
    opening_balance: number;
    closing_balance: number;
    net_change: number;
    inflows: number;
    outflows: number;
    entries: CashFlowEntry[];
}

interface Period {
    start_date: string;
    end_date: string;
}

interface Report {
    period: Period;
    cash_accounts: CashAccount[];
    total_opening: number;
    total_closing: number;
    total_inflows: number;
    total_outflows: number;
    net_change: number;
}

interface Filters {
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    report: Report;
    filters: Filters;
}>();

const showFilters = ref(false);
const selectedAccount = ref<number | null>(null);

const form = useForm({
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
        title: 'Flujo de Caja',
        href: '/accounting/reports/cash-flow',
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

const formatDateLong = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const applyFilters = () => {
    form.get('/accounting/reports/cash-flow', {
        preserveState: true,
    });
};

const exportReport = () => {
    window.location.href = `/accounting/reports/cash-flow/export?start_date=${props.filters.start_date}&end_date=${props.filters.end_date}`;
};

const selectedAccountData = computed(() => {
    if (selectedAccount.value === null) return null;
    return props.report.cash_accounts.find((acc) => acc.account.id === selectedAccount.value);
});

const cashFlowSummary = computed(() => {
    const total = props.report.total_opening + props.report.total_inflows - props.report.total_outflows;
    const isPositive = props.report.net_change > 0;
    const flowRatio = props.report.total_opening > 0 ? (props.report.net_change / props.report.total_opening) * 100 : 0;

    return {
        calculated_closing: total,
        is_balanced: Math.abs(total - props.report.total_closing) < 0.01,
        is_positive_flow: isPositive,
        flow_ratio: flowRatio,
        liquidity_status:
            props.report.total_closing > props.report.total_opening
                ? 'increased'
                : props.report.total_closing < props.report.total_opening
                  ? 'decreased'
                  : 'stable',
    };
});

const getFlowIcon = (type: 'inflow' | 'outflow') => {
    return type === 'inflow' ? ArrowDownLeft : ArrowUpRight;
};

const getFlowColor = (type: 'inflow' | 'outflow') => {
    return type === 'inflow' ? 'text-green-600' : 'text-red-600';
};

// Commented out for now as it's not being used
// const getAccountPerformance = (account: CashAccount) => {
//     const turnover = Math.max(account.inflows, account.outflows);
//     const netRatio = account.opening_balance > 0
//         ? (account.net_change / account.opening_balance) * 100
//         : 0;
//
//     return {
//         turnover,
//         net_ratio: netRatio,
//         activity_level: turnover > account.opening_balance * 0.5 ? 'high' :
//                        turnover > account.opening_balance * 0.1 ? 'medium' : 'low',
//     };
// };

const periodDays = computed(() => {
    const start = new Date(props.report.period.start_date);
    const end = new Date(props.report.period.end_date);
    return Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
});

const dailyAverage = computed(() => {
    return {
        inflows: props.report.total_inflows / periodDays.value,
        outflows: props.report.total_outflows / periodDays.value,
        net: props.report.net_change / periodDays.value,
    };
});
</script>

<template>
    <Head title="Flujo de Caja" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Flujo de Caja</h1>
                    <p class="text-muted-foreground">
                        Del {{ formatDateLong(report.period.start_date) }} al {{ formatDateLong(report.period.end_date) }}
                    </p>
                    <p class="text-sm text-muted-foreground">Período de {{ periodDays }} días</p>
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
                        Filtros de Período
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

            <!-- Cash Flow Summary -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Saldo Inicial</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ formatCurrency(report.total_opening) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Efectivo disponible al inicio</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <ArrowDownLeft class="h-4 w-4 text-green-600" />
                            Entradas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatCurrency(report.total_inflows) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">{{ formatCurrency(dailyAverage.inflows) }}/día promedio</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <ArrowUpRight class="h-4 w-4 text-red-600" />
                            Salidas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(report.total_outflows) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">{{ formatCurrency(dailyAverage.outflows) }}/día promedio</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Flujo Neto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.net_change >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(Math.abs(report.net_change)) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">{{ report.net_change >= 0 ? 'Generación' : 'Consumo' }} de efectivo</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Saldo Final</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.total_closing >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(Math.abs(report.total_closing)) }}
                        </div>
                        <div class="mt-2">
                            <span
                                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                :class="
                                    cashFlowSummary.liquidity_status === 'increased'
                                        ? 'bg-green-100 text-green-800'
                                        : cashFlowSummary.liquidity_status === 'decreased'
                                          ? 'bg-red-100 text-red-800'
                                          : 'bg-gray-100 text-gray-800'
                                "
                            >
                                {{
                                    cashFlowSummary.liquidity_status === 'increased'
                                        ? 'Mejoró'
                                        : cashFlowSummary.liquidity_status === 'decreased'
                                          ? 'Empeoró'
                                          : 'Estable'
                                }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Cash Accounts Detail -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <Wallet class="h-5 w-5" />
                        Detalle por Cuenta de Efectivo
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-24">Código</TableHead>
                                <TableHead>Cuenta</TableHead>
                                <TableHead class="text-right">Saldo Inicial</TableHead>
                                <TableHead class="text-right">Entradas</TableHead>
                                <TableHead class="text-right">Salidas</TableHead>
                                <TableHead class="text-right">Cambio Neto</TableHead>
                                <TableHead class="text-right">Saldo Final</TableHead>
                                <TableHead class="w-20">Detalle</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="cashAccount in report.cash_accounts" :key="cashAccount.account.id">
                                <TableCell class="font-mono text-sm">{{ cashAccount.account.code }}</TableCell>
                                <TableCell class="font-medium">{{ cashAccount.account.name }}</TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatCurrency(cashAccount.opening_balance) }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-green-600">
                                    {{ formatCurrency(cashAccount.inflows) }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-red-600">
                                    {{ formatCurrency(cashAccount.outflows) }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono font-medium"
                                    :class="cashAccount.net_change >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatCurrency(Math.abs(cashAccount.net_change)) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-medium">
                                    {{ formatCurrency(cashAccount.closing_balance) }}
                                </TableCell>
                                <TableCell>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="selectedAccount = selectedAccount === cashAccount.account.id ? null : cashAccount.account.id"
                                    >
                                        {{ selectedAccount === cashAccount.account.id ? 'Ocultar' : 'Ver' }}
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <!-- Totals Row -->
                            <TableRow class="border-t-2 bg-primary/5 font-bold">
                                <TableCell colspan="2" class="font-bold">TOTALES</TableCell>
                                <TableCell class="text-right font-mono font-bold">
                                    {{ formatCurrency(report.total_opening) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold text-green-600">
                                    {{ formatCurrency(report.total_inflows) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold text-red-600">
                                    {{ formatCurrency(report.total_outflows) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold" :class="report.net_change >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatCurrency(Math.abs(report.net_change)) }}
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold">
                                    {{ formatCurrency(report.total_closing) }}
                                </TableCell>
                                <TableCell></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Detailed Movements for Selected Account -->
            <Card v-if="selectedAccountData" class="border-2 border-primary/20">
                <CardHeader>
                    <CardTitle class="text-lg">
                        Movimientos Detallados - {{ selectedAccountData.account.code }} {{ selectedAccountData.account.name }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="selectedAccountData.entries.length === 0" class="py-8 text-center text-muted-foreground">
                        <Wallet class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">No hay movimientos en el período</p>
                        <p class="text-sm">Esta cuenta no registró transacciones en las fechas seleccionadas</p>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Account Summary -->
                        <div class="grid grid-cols-1 gap-4 rounded-lg bg-muted/30 p-4 md:grid-cols-4">
                            <div class="text-center">
                                <div class="text-lg font-bold">{{ formatCurrency(selectedAccountData.opening_balance) }}</div>
                                <p class="text-xs text-muted-foreground">Saldo Inicial</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-green-600">{{ formatCurrency(selectedAccountData.inflows) }}</div>
                                <p class="text-xs text-muted-foreground">Entradas</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-red-600">{{ formatCurrency(selectedAccountData.outflows) }}</div>
                                <p class="text-xs text-muted-foreground">Salidas</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold">{{ formatCurrency(selectedAccountData.closing_balance) }}</div>
                                <p class="text-xs text-muted-foreground">Saldo Final</p>
                            </div>
                        </div>

                        <Separator />

                        <!-- Movements Table -->
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-28">Fecha</TableHead>
                                    <TableHead class="w-32">Comprobante</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead class="w-32 text-right">Entradas</TableHead>
                                    <TableHead class="w-32 text-right">Salidas</TableHead>
                                    <TableHead class="w-8">Tipo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(entry, index) in selectedAccountData.entries" :key="index">
                                    <TableCell class="font-mono text-sm">
                                        {{ formatDate(entry.date) }}
                                    </TableCell>
                                    <TableCell class="font-mono text-sm">
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
                                    <TableCell>
                                        <component
                                            :is="getFlowIcon(entry.debit_amount > 0 ? 'inflow' : 'outflow')"
                                            :class="getFlowColor(entry.debit_amount > 0 ? 'inflow' : 'outflow')"
                                            class="h-4 w-4"
                                        />
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Analysis and Insights -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Análisis del Flujo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="rounded bg-muted/50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium">Ratio Entradas/Salidas</span>
                                    <span class="font-bold">
                                        {{ report.total_outflows > 0 ? (report.total_inflows / report.total_outflows).toFixed(2) : '∞' }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        report.total_inflows > report.total_outflows
                                            ? 'Generación positiva de efectivo'
                                            : report.total_inflows < report.total_outflows
                                              ? 'Consumo neto de efectivo'
                                              : 'Flujo balanceado'
                                    }}
                                </p>
                            </div>

                            <div class="rounded bg-muted/50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium">Variación de Liquidez</span>
                                    <span class="font-bold" :class="cashFlowSummary.flow_ratio >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ cashFlowSummary.flow_ratio.toFixed(1) }}%
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        Math.abs(cashFlowSummary.flow_ratio) > 20
                                            ? 'Cambio significativo'
                                            : Math.abs(cashFlowSummary.flow_ratio) > 5
                                              ? 'Cambio moderado'
                                              : 'Cambio mínimo'
                                    }}
                                    en la posición de efectivo
                                </p>
                            </div>

                            <div class="rounded bg-muted/50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium">Duración del Período</span>
                                    <span class="font-bold">{{ periodDays }} días</span>
                                </div>
                                <p class="text-xs text-muted-foreground">Flujo diario promedio: {{ formatCurrency(Math.abs(dailyAverage.net)) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Resumen por Cuenta</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="account in report.cash_accounts"
                                :key="account.account.id"
                                class="flex items-center justify-between rounded bg-muted/50 p-2"
                            >
                                <div>
                                    <div class="text-sm font-medium">{{ account.account.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ account.account.code }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold">{{ formatCurrency(account.closing_balance) }}</div>
                                    <div class="text-xs" :class="account.net_change >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ account.net_change >= 0 ? '+' : '' }}{{ formatCurrency(account.net_change) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
