<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Download, Filter, TrendingDown, TrendingUp } from 'lucide-vue-next';
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

interface Period {
    start_date: string;
    end_date: string;
    description: string;
}

interface Report {
    period: Period;
    income: Account[];
    expenses: Account[];
    total_income: number;
    total_expenses: number;
    net_income: number;
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
        title: 'Estado de Resultados',
        href: '/accounting/reports/income-statement',
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

const applyFilters = () => {
    form.get('/accounting/reports/income-statement', {
        preserveState: true,
    });
};

const exportReport = () => {
    window.location.href = `/accounting/reports/income-statement/export?start_date=${props.filters.start_date}&end_date=${props.filters.end_date}`;
};

const profitMargin = computed(() => {
    return props.report.total_income > 0 ? (props.report.net_income / props.report.total_income) * 100 : 0;
});

const expenseRatio = computed(() => {
    return props.report.total_income > 0 ? (props.report.total_expenses / props.report.total_income) * 100 : 0;
});

const isPositiveResult = computed(() => props.report.net_income > 0);

// Categorize expenses for better visualization
const categorizedExpenses = computed(() => {
    const categories = {
        administrative: props.report.expenses.filter((account) => account.code.startsWith('51')),
        operational: props.report.expenses.filter((account) => account.code.startsWith('52')),
        maintenance: props.report.expenses.filter((account) => account.code.startsWith('53')),
        financial: props.report.expenses.filter((account) => account.code.startsWith('54')),
        other: props.report.expenses.filter((account) => !['51', '52', '53', '54'].some((prefix) => account.code.startsWith(prefix))),
    };

    return Object.entries(categories)
        .filter(([, accounts]) => accounts.length > 0)
        .map(([category, accounts]) => ({
            category,
            categoryName: getCategoryName(category),
            accounts,
            total: accounts.reduce((sum, account) => sum + account.balance, 0),
        }));
});

const getCategoryName = (category: string) => {
    const names = {
        administrative: 'Gastos Administrativos',
        operational: 'Gastos Operacionales',
        maintenance: 'Gastos de Mantenimiento',
        financial: 'Gastos Financieros',
        other: 'Otros Gastos',
    };
    return names[category] || 'Otros Gastos';
};
</script>

<template>
    <Head title="Estado de Resultados" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Estado de Resultados</h1>
                    <p class="text-muted-foreground">
                        {{ report.period.description }}
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

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ingresos Totales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatCurrency(report.total_income) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Base para el cálculo</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Gastos Totales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(report.total_expenses) }}
                        </div>
                        <div class="mt-1 flex items-center">
                            <Progress
                                :value="expenseRatio"
                                class="h-2 flex-1"
                                :class="expenseRatio > 80 ? 'bg-red-100' : expenseRatio > 60 ? 'bg-yellow-100' : 'bg-green-100'"
                            />
                            <span class="ml-2 text-xs text-muted-foreground">{{ expenseRatio.toFixed(1) }}%</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Resultado Neto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2 text-2xl font-bold" :class="isPositiveResult ? 'text-green-600' : 'text-red-600'">
                            <component :is="isPositiveResult ? TrendingUp : TrendingDown" class="h-5 w-5" />
                            {{ formatCurrency(Math.abs(report.net_income)) }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ isPositiveResult ? 'Superávit' : 'Déficit' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Margen</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="profitMargin > 0 ? 'text-green-600' : 'text-red-600'">
                            {{ profitMargin.toFixed(1) }}%
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ profitMargin > 10 ? 'Excelente' : profitMargin > 0 ? 'Positivo' : 'Negativo' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Income Statement -->
            <div class="space-y-6">
                <!-- Income Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl text-green-700">Ingresos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-20">Código</TableHead>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Importe</TableHead>
                                    <TableHead class="w-20 text-right">%</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="account in report.income" :key="account.id">
                                    <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                    <TableCell class="font-medium">{{ account.name }}</TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ formatCurrency(account.balance) }}
                                    </TableCell>
                                    <TableCell class="text-right text-sm text-muted-foreground">
                                        {{ report.total_income > 0 ? ((account.balance / report.total_income) * 100).toFixed(1) : 0 }}%
                                    </TableCell>
                                </TableRow>
                                <TableRow class="border-t-2 bg-green-50">
                                    <TableCell colspan="2" class="font-bold text-green-700">Total Ingresos</TableCell>
                                    <TableCell class="text-right font-mono font-bold text-green-700">
                                        {{ formatCurrency(report.total_income) }}
                                    </TableCell>
                                    <TableCell class="text-right font-bold text-green-700">100.0%</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Expenses Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl text-red-700">Gastos</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div v-for="category in categorizedExpenses" :key="category.category" class="space-y-3">
                            <h3 class="text-lg font-semibold text-red-600">{{ category.categoryName }}</h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20">Código</TableHead>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead class="text-right">Importe</TableHead>
                                        <TableHead class="w-20 text-right">%</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="account in category.accounts" :key="account.id">
                                        <TableCell class="font-mono text-sm">{{ account.code }}</TableCell>
                                        <TableCell class="font-medium">{{ account.name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(account.balance) }}
                                        </TableCell>
                                        <TableCell class="text-right text-sm text-muted-foreground">
                                            {{ report.total_income > 0 ? ((account.balance / report.total_income) * 100).toFixed(1) : 0 }}%
                                        </TableCell>
                                    </TableRow>
                                    <TableRow class="border-t bg-red-50">
                                        <TableCell colspan="2" class="font-semibold text-red-600"> Subtotal {{ category.categoryName }} </TableCell>
                                        <TableCell class="text-right font-mono font-semibold text-red-600">
                                            {{ formatCurrency(category.total) }}
                                        </TableCell>
                                        <TableCell class="text-right font-semibold text-red-600">
                                            {{ report.total_income > 0 ? ((category.total / report.total_income) * 100).toFixed(1) : 0 }}%
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <Separator v-if="categorizedExpenses.indexOf(category) !== categorizedExpenses.length - 1" />
                        </div>

                        <!-- Total Expenses -->
                        <div class="border-t-2 pt-4">
                            <Table>
                                <TableBody>
                                    <TableRow class="bg-red-100">
                                        <TableCell class="font-bold text-red-700">Total Gastos</TableCell>
                                        <TableCell class="text-right font-mono font-bold text-red-700">
                                            {{ formatCurrency(report.total_expenses) }}
                                        </TableCell>
                                        <TableCell class="w-20 text-right font-bold text-red-700"> {{ expenseRatio.toFixed(1) }}% </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Net Result -->
                <Card :class="isPositiveResult ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                    <CardContent class="pt-6">
                        <Table>
                            <TableBody>
                                <TableRow class="border-none">
                                    <TableCell class="text-xl font-bold" :class="isPositiveResult ? 'text-green-700' : 'text-red-700'">
                                        {{ isPositiveResult ? 'Superávit del Período' : 'Déficit del Período' }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-mono text-2xl font-bold"
                                        :class="isPositiveResult ? 'text-green-700' : 'text-red-700'"
                                    >
                                        {{ formatCurrency(Math.abs(report.net_income)) }}
                                    </TableCell>
                                    <TableCell
                                        class="w-20 text-right text-xl font-bold"
                                        :class="isPositiveResult ? 'text-green-700' : 'text-red-700'"
                                    >
                                        {{ profitMargin.toFixed(1) }}%
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
