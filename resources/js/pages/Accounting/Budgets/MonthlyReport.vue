<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';

interface Budget {
    id: number;
    name: string;
    fiscal_year: number;
    status: string;
}

interface MonthlyData {
    month: number;
    month_name: string;
    budgeted_income: number;
    budgeted_expenses: number;
    budgeted_net: number;
    executed_income: number;
    executed_expenses: number;
    executed_net: number;
    variance_income: number;
    variance_expenses: number;
    variance_net: number;
    income_execution_percentage: number;
    expenses_execution_percentage: number;
}

interface Totals {
    budgeted_income: number;
    budgeted_expenses: number;
    budgeted_net: number;
    executed_income: number;
    executed_expenses: number;
    executed_net: number;
    variance_income: number;
    variance_expenses: number;
    variance_net: number;
    income_execution_percentage: number;
    expenses_execution_percentage: number;
}

const props = defineProps<{
    budget: Budget;
    monthlyData: MonthlyData[];
    totals: Totals;
}>();

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Presupuestos', href: '/accounting/budgets' },
    { title: props.budget.name, href: `/accounting/budgets/${props.budget.id}` },
    { title: 'Reporte Mensual' },
];

// Format currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

// Format percentage
const formatPercentage = (value: number) => {
    return `${value.toFixed(1)}%`;
};

// Get variance icon and color
const getVarianceIndicator = (variance: number) => {
    if (variance > 0) {
        return { icon: TrendingUp, class: 'text-green-600' };
    } else if (variance < 0) {
        return { icon: TrendingDown, class: 'text-red-600' };
    }
    return { icon: Minus, class: 'text-gray-400' };
};

// Get execution percentage badge variant
const getExecutionBadgeVariant = (percentage: number) => {
    if (percentage >= 90 && percentage <= 110) return 'default';
    if (percentage > 110) return 'destructive';
    return 'secondary';
};
</script>

<template>

    <Head :title="`Reporte Mensual - ${budget.name}`" />

    <AppLayout :title="`Reporte Mensual - ${budget.name}`" :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Reporte Mensual</h1>
                    <p class="text-muted-foreground">
                        Presupuestado vs Ejecutado por mes - {{ budget.name }} ({{ budget.fiscal_year }})
                    </p>
                </div>
                <div class="flex space-x-2">
                    <Link :href="`/accounting/budgets/${budget.id}`">
                    <Button variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Ingresos Presupuestados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totals.budgeted_income) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Ingresos Ejecutados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totals.executed_income) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatPercentage(totals.income_execution_percentage) }} del presupuesto
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Gastos Presupuestados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totals.budgeted_expenses) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Gastos Ejecutados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totals.executed_expenses) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatPercentage(totals.expenses_execution_percentage) }} del presupuesto
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Monthly Report Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Detalle Mensual</CardTitle>
                    <CardDescription>Presupuestado vs Ejecutado por cada mes del año {{ budget.fiscal_year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Mes</TableHead>
                                <TableHead class="text-right">Ingresos Presup.</TableHead>
                                <TableHead class="text-right">Ingresos Ejec.</TableHead>
                                <TableHead class="text-right">% Ejec.</TableHead>
                                <TableHead class="text-right">Gastos Presup.</TableHead>
                                <TableHead class="text-right">Gastos Ejec.</TableHead>
                                <TableHead class="text-right">% Ejec.</TableHead>
                                <TableHead class="text-right">Resultado Neto</TableHead>
                                <TableHead class="text-right">Var. Neta</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="month in monthlyData" :key="month.month">
                                <TableCell class="font-medium">{{ month.month_name }}</TableCell>

                                <!-- Income -->
                                <TableCell class="text-right">{{ formatCurrency(month.budgeted_income) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(month.executed_income) }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge :variant="getExecutionBadgeVariant(month.income_execution_percentage)">
                                        {{ formatPercentage(month.income_execution_percentage) }}
                                    </Badge>
                                </TableCell>

                                <!-- Expenses -->
                                <TableCell class="text-right">{{ formatCurrency(month.budgeted_expenses) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(month.executed_expenses) }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge :variant="getExecutionBadgeVariant(month.expenses_execution_percentage)">
                                        {{ formatPercentage(month.expenses_execution_percentage) }}
                                    </Badge>
                                </TableCell>

                                <!-- Net -->
                                <TableCell class="text-right font-medium">
                                    <span :class="month.executed_net >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(month.executed_net) }}
                                    </span>
                                </TableCell>

                                <!-- Variance -->
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <component :is="getVarianceIndicator(month.variance_net).icon"
                                            :class="['h-4 w-4', getVarianceIndicator(month.variance_net).class]" />
                                        <span :class="getVarianceIndicator(month.variance_net).class">
                                            {{ formatCurrency(Math.abs(month.variance_net)) }}
                                        </span>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Totals Row -->
                            <TableRow class="bg-muted/50 font-bold">
                                <TableCell>TOTAL ANUAL</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.budgeted_income) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.executed_income) }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge variant="outline">
                                        {{ formatPercentage(totals.income_execution_percentage) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.budgeted_expenses) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.executed_expenses) }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge variant="outline">
                                        {{ formatPercentage(totals.expenses_execution_percentage) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <span :class="totals.executed_net >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(totals.executed_net) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <span :class="getVarianceIndicator(totals.variance_net).class">
                                        {{ formatCurrency(Math.abs(totals.variance_net)) }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
