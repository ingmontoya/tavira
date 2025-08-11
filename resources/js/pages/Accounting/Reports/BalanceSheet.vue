<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Download, FileText, Filter } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Account {
    id: number;
    code: string;
    name: string;
    full_name: string;
    account_type: string;
    nature: string;
    balance: number;
}

interface Report {
    as_of_date: string;
    assets: Account[];
    liabilities: Account[];
    equity: Account[];
    total_assets: number;
    total_liabilities: number;
    total_equity: number;
    total_liabilities_equity: number;
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
        title: 'Balance General',
        href: '/accounting/reports/balance-sheet',
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
    form.get('/accounting/reports/balance-sheet', {
        preserveState: true,
    });
};

const exportReport = () => {
    window.location.href = `/accounting/reports/balance-sheet/export?as_of_date=${props.filters.as_of_date}`;
};

const currentRatio = computed(() => {
    const currentAssets = props.report.assets.filter(account => 
        account.code.startsWith('11') // Activos corrientes
    ).reduce((sum, account) => sum + account.balance, 0);
    
    const currentLiabilities = props.report.liabilities.filter(account => 
        account.code.startsWith('21') // Pasivos corrientes
    ).reduce((sum, account) => sum + account.balance, 0);
    
    return currentLiabilities > 0 ? currentAssets / currentLiabilities : 0;
});

const debtToEquityRatio = computed(() => {
    return props.report.total_equity > 0 
        ? props.report.total_liabilities / props.report.total_equity 
        : 0;
});
</script>

<template>
    <Head title="Balance General" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Balance General</h1>
                    <p class="text-muted-foreground">
                        Estado de Situación Financiera al {{ formatDate(report.as_of_date) }}
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

            <!-- Balance Status Alert -->
            <Card v-if="!report.is_balanced" class="border-amber-200 bg-amber-50">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-amber-800">
                        <AlertTriangle class="h-5 w-5" />
                        <p class="font-medium">
                            Advertencia: El balance no está balanceado. 
                            Diferencia: {{ formatCurrency(Math.abs(report.total_assets - report.total_liabilities_equity)) }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Financial Ratios -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Razón Corriente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currentRatio.toFixed(2) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ currentRatio >= 1.5 ? 'Buena liquidez' : currentRatio >= 1 ? 'Liquidez adecuada' : 'Baja liquidez' }}
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Endeudamiento</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ debtToEquityRatio.toFixed(2) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ debtToEquityRatio <= 0.5 ? 'Bajo endeudamiento' : debtToEquityRatio <= 1 ? 'Endeudamiento moderado' : 'Alto endeudamiento' }}
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Balance</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="report.is_balanced ? 'text-green-600' : 'text-red-600'">
                            {{ report.is_balanced ? '✓' : '✗' }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ report.is_balanced ? 'Balanceado' : 'Desbalanceado' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Balance Sheet -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Assets -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Activos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-20">Código</TableHead>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="account in report.assets" :key="account.id">
                                    <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                    <TableCell class="font-medium">{{ account.name }}</TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ formatCurrency(account.balance) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow class="border-t-2 bg-muted/50">
                                    <TableCell colspan="2" class="font-bold">Total Activos</TableCell>
                                    <TableCell class="text-right font-bold font-mono">
                                        {{ formatCurrency(report.total_assets) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Liabilities & Equity -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Pasivos y Patrimonio
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Liabilities -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Pasivos</h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20">Código</TableHead>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead class="text-right">Saldo</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="account in report.liabilities" :key="account.id">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(account.balance) }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow class="border-t bg-muted/30">
                                        <TableCell colspan="2" class="font-semibold">Total Pasivos</TableCell>
                                        <TableCell class="text-right font-semibold font-mono">
                                            {{ formatCurrency(report.total_liabilities) }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <Separator />

                        <!-- Equity -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Patrimonio</h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20">Código</TableHead>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead class="text-right">Saldo</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="account in report.equity" :key="account.id">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(account.balance) }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow class="border-t bg-muted/30">
                                        <TableCell colspan="2" class="font-semibold">Total Patrimonio</TableCell>
                                        <TableCell class="text-right font-semibold font-mono">
                                            {{ formatCurrency(report.total_equity) }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow class="border-t-2 bg-primary/10">
                                        <TableCell colspan="2" class="font-bold">Total Pasivos y Patrimonio</TableCell>
                                        <TableCell class="text-right font-bold font-mono">
                                            {{ formatCurrency(report.total_liabilities_equity) }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>