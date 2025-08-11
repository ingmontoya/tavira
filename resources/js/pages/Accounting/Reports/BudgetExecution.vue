<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, BarChart3, Calendar, Download, Filter, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Budget {
    id: number;
    name: string;
    fiscal_year: number;
    status: string;
}

interface Period {
    month: number;
    year: number;
    name: string;
}

interface BudgetItem {
    account_code: string;
    account_name: string;
    budgeted_amount: number;
    actual_amount: number;
    variance: number;
    variance_percentage: number;
    execution_percentage: number;
}

interface ExecutionSummary {
    total_budgeted: number;
    total_actual: number;
    total_variance: number;
    execution_percentage: number;
    items: BudgetItem[];
}

interface Report {
    budget: Budget;
    period: Period;
    monthly_execution: ExecutionSummary;
    ytd_execution: ExecutionSummary;
}

interface Filters {
    budget_id: number;
    period_month: number;
    period_year: number;
}

const props = defineProps<{
    report: Report;
    filters: Filters;
    budgets: Budget[];
    availableMonths: { [key: number]: string };
}>();

const showFilters = ref(false);

const form = useForm({
    budget_id: props.filters.budget_id,
    period_month: props.filters.period_month,
    period_year: props.filters.period_year,
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
        title: 'Ejecución Presupuestal',
        href: '/accounting/reports/budget-execution',
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
    form.get('/accounting/reports/budget-execution', {
        preserveState: true,
    });
};

const exportReport = () => {
    const params = new URLSearchParams({
        budget_id: props.filters.budget_id.toString(),
        period_month: props.filters.period_month.toString(),
        period_year: props.filters.period_year.toString(),
    });
    window.location.href = `/accounting/reports/budget-execution/export?${params.toString()}`;
};

const getVarianceColor = (variance: number, percentage: number) => {
    if (Math.abs(percentage) < 5) return 'text-green-600';
    if (Math.abs(percentage) < 15) return 'text-yellow-600';
    return 'text-red-600';
};

const getVarianceIcon = (variance: number) => {
    return variance > 0 ? TrendingUp : TrendingDown;
};

const getExecutionColor = (percentage: number) => {
    if (percentage >= 90) return 'text-green-600';
    if (percentage >= 70) return 'text-yellow-600';
    return 'text-red-600';
};

const monthlyPerformance = computed(() => {
    const execution = props.report.monthly_execution;
    const isOverBudget = execution.total_actual > execution.total_budgeted;
    const criticalItems = execution.items.filter(item => Math.abs(item.variance_percentage) > 20);
    
    return {
        status: isOverBudget ? 'over' : execution.execution_percentage > 90 ? 'excellent' : execution.execution_percentage > 70 ? 'good' : 'poor',
        criticalItems: criticalItems.length,
        avgExecution: execution.items.length > 0 
            ? execution.items.reduce((sum, item) => sum + item.execution_percentage, 0) / execution.items.length 
            : 0,
    };
});

const ytdPerformance = computed(() => {
    const execution = props.report.ytd_execution;
    const monthsElapsed = props.report.period.month;
    const expectedExecution = (monthsElapsed / 12) * 100;
    const performanceRatio = execution.execution_percentage / expectedExecution;
    
    return {
        expectedExecution,
        performanceRatio,
        status: performanceRatio > 1.1 ? 'ahead' : performanceRatio > 0.9 ? 'ontrack' : 'behind',
        projection: execution.execution_percentage > 0 
            ? (execution.total_actual / monthsElapsed) * 12 
            : 0,
    };
});

const getStatusBadge = (status: string) => {
    const badges = {
        excellent: { label: 'Excelente', class: 'bg-green-100 text-green-800' },
        good: { label: 'Bueno', class: 'bg-yellow-100 text-yellow-800' },
        poor: { label: 'Deficiente', class: 'bg-red-100 text-red-800' },
        over: { label: 'Sobre presupuesto', class: 'bg-red-100 text-red-800' },
        ahead: { label: 'Adelantado', class: 'bg-blue-100 text-blue-800' },
        ontrack: { label: 'En línea', class: 'bg-green-100 text-green-800' },
        behind: { label: 'Atrasado', class: 'bg-orange-100 text-orange-800' },
    };
    return badges[status] || badges.poor;
};

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);
});
</script>

<template>
    <Head title="Ejecución Presupuestal" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Ejecución Presupuestal</h1>
                    <p class="text-muted-foreground">
                        {{ report.budget.name }} - {{ report.period.name }}
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
                        Filtros de Ejecución
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <label for="budget_id" class="text-sm font-medium">Presupuesto</label>
                            <Select v-model="form.budget_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar presupuesto" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="budget in budgets" :key="budget.id" :value="budget.id.toString()">
                                        {{ budget.name }} ({{ budget.fiscal_year }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label for="period_month" class="text-sm font-medium">Mes</label>
                            <Select v-model="form.period_month">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar mes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(monthName, monthNum) in availableMonths" 
                                                :key="monthNum" 
                                                :value="monthNum.toString()">
                                        {{ monthName }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label for="period_year" class="text-sm font-medium">Año</label>
                            <Select v-model="form.period_year">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar año" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
                                        {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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

            <!-- Performance Overview -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ejecución Mensual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getExecutionColor(report.monthly_execution.execution_percentage)">
                            {{ report.monthly_execution.execution_percentage.toFixed(1) }}%
                        </div>
                        <div class="mt-2">
                            <Progress :value="Math.min(report.monthly_execution.execution_percentage, 100)" 
                                      class="h-2" />
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold" 
                                  :class="getStatusBadge(monthlyPerformance.status).class">
                                {{ getStatusBadge(monthlyPerformance.status).label }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ejecución Acumulada</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getExecutionColor(report.ytd_execution.execution_percentage)">
                            {{ report.ytd_execution.execution_percentage.toFixed(1) }}%
                        </div>
                        <div class="mt-2">
                            <Progress :value="Math.min(report.ytd_execution.execution_percentage, 100)" 
                                      class="h-2" />
                        </div>
                        <div class="mt-2 text-xs text-muted-foreground">
                            Esperado: {{ ytdPerformance.expectedExecution.toFixed(1) }}%
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Variación Mensual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold flex items-center gap-2" 
                             :class="getVarianceColor(report.monthly_execution.total_variance, 
                                    (report.monthly_execution.total_variance / report.monthly_execution.total_budgeted) * 100)">
                            <component :is="getVarianceIcon(report.monthly_execution.total_variance)" class="h-5 w-5" />
                            {{ formatCurrency(Math.abs(report.monthly_execution.total_variance)) }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ ((report.monthly_execution.total_variance / report.monthly_execution.total_budgeted) * 100).toFixed(1) }}% 
                            {{ report.monthly_execution.total_variance > 0 ? 'sobre' : 'bajo' }} presupuesto
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Proyección Anual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(ytdPerformance.projection) }}
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold" 
                                  :class="getStatusBadge(ytdPerformance.status).class">
                                {{ getStatusBadge(ytdPerformance.status).label }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Critical Items Alert -->
            <Card v-if="monthlyPerformance.criticalItems > 0" class="border-amber-200 bg-amber-50">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-amber-800">
                        <AlertTriangle class="h-5 w-5" />
                        <p class="font-medium">
                            {{ monthlyPerformance.criticalItems }} partida{{ monthlyPerformance.criticalItems !== 1 ? 's' : '' }} 
                            con variación mayor al 20%
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Detailed Execution -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-xl flex items-center gap-2">
                        <BarChart3 class="h-5 w-5" />
                        Ejecución Detallada
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Tabs defaultValue="monthly" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="monthly">Ejecución Mensual</TabsTrigger>
                            <TabsTrigger value="ytd">Acumulado del Año</TabsTrigger>
                        </TabsList>

                        <TabsContent value="monthly" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ formatCurrency(report.monthly_execution.total_budgeted) }}
                                    </div>
                                    <p class="text-sm text-blue-600 font-medium">Presupuestado</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ formatCurrency(report.monthly_execution.total_actual) }}
                                    </div>
                                    <p class="text-sm text-green-600 font-medium">Ejecutado</p>
                                </div>
                                <div class="text-center p-4 rounded-lg"
                                     :class="report.monthly_execution.total_variance > 0 ? 'bg-red-50' : 'bg-green-50'">
                                    <div class="text-2xl font-bold"
                                         :class="report.monthly_execution.total_variance > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ formatCurrency(Math.abs(report.monthly_execution.total_variance)) }}
                                    </div>
                                    <p class="text-sm font-medium"
                                       :class="report.monthly_execution.total_variance > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ report.monthly_execution.total_variance > 0 ? 'Sobregiro' : 'Ahorro' }}
                                    </p>
                                </div>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20">Código</TableHead>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead class="text-right">Presupuestado</TableHead>
                                        <TableHead class="text-right">Ejecutado</TableHead>
                                        <TableHead class="text-right">Variación</TableHead>
                                        <TableHead class="text-right">% Ejecución</TableHead>
                                        <TableHead class="w-24">Estado</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in report.monthly_execution.items" :key="item.account_code">
                                        <TableCell class="font-mono text-sm">{{ item.account_code }}</TableCell>
                                        <TableCell class="font-medium">{{ item.account_name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.budgeted_amount) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.actual_amount) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono"
                                                   :class="getVarianceColor(item.variance, item.variance_percentage)">
                                            {{ formatCurrency(Math.abs(item.variance)) }}
                                            ({{ item.variance_percentage.toFixed(1) }}%)
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex items-center gap-2">
                                                <Progress :value="Math.min(item.execution_percentage, 100)" 
                                                          class="flex-1 h-2" />
                                                <span class="text-xs font-medium w-12" 
                                                      :class="getExecutionColor(item.execution_percentage)">
                                                    {{ item.execution_percentage.toFixed(0) }}%
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <component :is="getVarianceIcon(item.variance)" 
                                                       :class="getVarianceColor(item.variance, item.variance_percentage)"
                                                       class="h-4 w-4" />
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </TabsContent>

                        <TabsContent value="ytd" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ formatCurrency(report.ytd_execution.total_budgeted) }}
                                    </div>
                                    <p class="text-sm text-blue-600 font-medium">Presupuesto Anual</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ formatCurrency(report.ytd_execution.total_actual) }}
                                    </div>
                                    <p class="text-sm text-green-600 font-medium">Ejecutado a la Fecha</p>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600">
                                        {{ formatCurrency(ytdPerformance.projection) }}
                                    </div>
                                    <p class="text-sm text-purple-600 font-medium">Proyección Anual</p>
                                </div>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-20">Código</TableHead>
                                        <TableHead>Cuenta</TableHead>
                                        <TableHead class="text-right">Presupuesto Anual</TableHead>
                                        <TableHead class="text-right">Ejecutado YTD</TableHead>
                                        <TableHead class="text-right">% Ejecución</TableHead>
                                        <TableHead class="text-right">Proyección</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in report.ytd_execution.items" :key="item.account_code">
                                        <TableCell class="font-mono text-sm">{{ item.account_code }}</TableCell>
                                        <TableCell class="font-medium">{{ item.account_name }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.budgeted_amount) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.actual_amount) }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex items-center gap-2">
                                                <Progress :value="Math.min(item.execution_percentage, 100)" 
                                                          class="flex-1 h-2" />
                                                <span class="text-xs font-medium w-12" 
                                                      :class="getExecutionColor(item.execution_percentage)">
                                                    {{ item.execution_percentage.toFixed(0) }}%
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency((item.actual_amount / report.period.month) * 12) }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>