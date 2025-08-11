<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, Calendar, Download, RefreshCw, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Budget {
    id: number;
    name: string;
    fiscal_year: number;
    status: string;
    start_date: string;
    end_date: string;
}

interface Period {
    month: number;
    year: number;
}

interface Account {
    id: number;
    code: string;
    name: string;
    account_type: string;
}

interface BudgetItem {
    id: number;
    account: Account;
    budgeted_amount: number;
    category: 'income' | 'expense';
    notes?: string;
}

interface ExecutionItem {
    id: number;
    budget_item_id: number;
    period_month: number;
    period_year: number;
    budgeted_amount: number;
    actual_amount: number;
    variance_amount: number;
    variance_percentage: number;
    execution_percentage: number;
    budgetItem: BudgetItem;
}

interface ExecutionSummary {
    total_income_budgeted: number;
    total_income_actual: number;
    total_expenses_budgeted: number;
    total_expenses_actual: number;
    income: ExecutionItem[];
    expenses: ExecutionItem[];
}

const props = defineProps<{
    budget: Budget;
    executionSummary: ExecutionSummary;
    currentPeriod: Period;
    availableMonths: { [key: number]: string };
}>();

const selectedMonth = ref(props.currentPeriod.month);
const selectedYear = ref(props.currentPeriod.year);
const isRefreshing = ref(false);

const breadcrumbs = computed(() => [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Contabilidad',
        href: '/accounting',
    },
    {
        title: 'Presupuestos',
        href: '/accounting/budgets',
    },
    {
        title: props.budget.name,
        href: `/accounting/budgets/${props.budget.id}`,
    },
    {
        title: 'Ejecución',
    },
]);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
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

const updatePeriod = () => {
    router.visit(`/accounting/budgets/${props.budget.id}/execution`, {
        data: {
            month: selectedMonth.value,
            year: selectedYear.value,
        },
        preserveState: false,
    });
};

const refreshExecution = async () => {
    isRefreshing.value = true;
    try {
        await router.post('/accounting/budgets/refresh-execution', {
            month: selectedMonth.value,
            year: selectedYear.value,
        }, {
            preserveState: false,
        });
    } finally {
        isRefreshing.value = false;
    }
};

const exportExecution = () => {
    const params = new URLSearchParams({
        month: selectedMonth.value.toString(),
        year: selectedYear.value.toString(),
    });
    window.location.href = `/accounting/budgets/${props.budget.id}/execution/export?${params.toString()}`;
};

const goBack = () => {
    router.visit(`/accounting/budgets/${props.budget.id}`);
};

const availableYears = computed(() => {
    const budgetYear = props.budget.fiscal_year;
    return [budgetYear - 1, budgetYear, budgetYear + 1];
});

const incomePerformance = computed(() => {
    const total = props.executionSummary.total_income_budgeted || 0;
    const executed = props.executionSummary.total_income_actual || 0;
    const variance = executed - total;
    const percentage = total > 0 ? (executed / total) * 100 : 0;
    
    return {
        total,
        executed,
        variance,
        percentage,
    };
});

const expensesPerformance = computed(() => {
    const total = props.executionSummary.total_expenses_budgeted || 0;
    const executed = props.executionSummary.total_expenses_actual || 0;
    const variance = executed - total;
    const percentage = total > 0 ? (executed / total) * 100 : 0;
    
    return {
        total,
        executed,
        variance,
        percentage,
    };
});

const overallPerformance = computed(() => {
    const totalBudgeted = incomePerformance.value.total + expensesPerformance.value.total;
    const totalExecuted = incomePerformance.value.executed + expensesPerformance.value.executed;
    const totalVariance = incomePerformance.value.variance + expensesPerformance.value.variance;
    
    return {
        total: totalBudgeted,
        executed: totalExecuted,
        variance: totalVariance,
        percentage: totalBudgeted > 0 ? (totalExecuted / totalBudgeted) * 100 : 0,
    };
});
</script>

<template>
    <Head :title="`Ejecución - ${budget.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Ejecución Presupuestal</h1>
                        <p class="text-muted-foreground">
                            {{ budget.name }} - {{ availableMonths[currentPeriod.month] }} {{ currentPeriod.year }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <Button 
                        variant="outline" 
                        @click="refreshExecution"
                        :disabled="isRefreshing"
                    >
                        <RefreshCw :class="['mr-2 h-4 w-4', { 'animate-spin': isRefreshing }]" />
                        Actualizar
                    </Button>
                    <Button variant="outline" @click="exportExecution">
                        <Download class="mr-2 h-4 w-4" />
                        Exportar
                    </Button>
                </div>
            </div>

            <!-- Period Selector -->
            <Card class="border-dashed">
                <CardHeader>
                    <CardTitle class="text-lg flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Seleccionar Período
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Mes</label>
                            <Select v-model="selectedMonth">
                                <SelectTrigger class="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(monthName, monthNum) in availableMonths" 
                                                :key="monthNum" 
                                                :value="monthNum">
                                        {{ monthName }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Año</label>
                            <Select v-model="selectedYear">
                                <SelectTrigger class="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button @click="updatePeriod">
                            Actualizar Período
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Performance Overview -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ingresos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getExecutionColor(incomePerformance.percentage)">
                            {{ incomePerformance.percentage.toFixed(1) }}%
                        </div>
                        <div class="mt-2">
                            <Progress :value="Math.min(incomePerformance.percentage, 100)" class="h-2" />
                        </div>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ formatCurrency(incomePerformance.executed) }} de {{ formatCurrency(incomePerformance.total) }}
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Gastos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getExecutionColor(expensesPerformance.percentage)">
                            {{ expensesPerformance.percentage.toFixed(1) }}%
                        </div>
                        <div class="mt-2">
                            <Progress :value="Math.min(expensesPerformance.percentage, 100)" class="h-2" />
                        </div>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ formatCurrency(expensesPerformance.executed) }} de {{ formatCurrency(expensesPerformance.total) }}
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ejecución Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getExecutionColor(overallPerformance.percentage)">
                            {{ overallPerformance.percentage.toFixed(1) }}%
                        </div>
                        <div class="mt-2">
                            <Progress :value="Math.min(overallPerformance.percentage, 100)" class="h-2" />
                        </div>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ formatCurrency(overallPerformance.executed) }} de {{ formatCurrency(overallPerformance.total) }}
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Variación Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold flex items-center gap-2" 
                             :class="getVarianceColor(overallPerformance.variance, 
                                    overallPerformance.total > 0 ? (overallPerformance.variance / overallPerformance.total) * 100 : 0)">
                            <component :is="getVarianceIcon(overallPerformance.variance)" class="h-5 w-5" />
                            {{ formatCurrency(Math.abs(overallPerformance.variance)) }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ overallPerformance.total > 0 ? ((overallPerformance.variance / overallPerformance.total) * 100).toFixed(1) : 0 }}% 
                            {{ overallPerformance.variance > 0 ? 'sobre' : 'bajo' }} presupuesto
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Detailed Execution -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-xl flex items-center gap-2">
                        <BarChart3 class="h-5 w-5" />
                        Ejecución Detallada
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Tabs defaultValue="income" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="income">Ingresos</TabsTrigger>
                            <TabsTrigger value="expenses">Gastos</TabsTrigger>
                        </TabsList>

                        <TabsContent value="income" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ formatCurrency(incomePerformance.total) }}
                                    </div>
                                    <p class="text-sm text-blue-600 font-medium">Presupuestado</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ formatCurrency(incomePerformance.executed) }}
                                    </div>
                                    <p class="text-sm text-green-600 font-medium">Ejecutado</p>
                                </div>
                                <div class="text-center p-4 rounded-lg"
                                     :class="incomePerformance.variance > 0 ? 'bg-green-50' : 'bg-red-50'">
                                    <div class="text-2xl font-bold"
                                         :class="incomePerformance.variance > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(Math.abs(incomePerformance.variance)) }}
                                    </div>
                                    <p class="text-sm font-medium"
                                       :class="incomePerformance.variance > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ incomePerformance.variance > 0 ? 'Exceso' : 'Faltante' }}
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
                                    <TableRow v-for="item in executionSummary.income" :key="item.id">
                                        <TableCell class="font-mono text-sm">{{ item.budgetItem?.account?.code || 'N/A' }}</TableCell>
                                        <TableCell class="font-medium">{{ item.budgetItem?.account?.name || 'N/A' }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.budgeted_amount || 0) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.actual_amount || 0) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono"
                                                   :class="getVarianceColor(item.variance_amount || 0, item.variance_percentage || 0)">
                                            {{ formatCurrency(Math.abs(item.variance_amount || 0)) }}
                                            ({{ (item.variance_percentage || 0).toFixed(1) }}%)
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex items-center gap-2">
                                                <Progress :value="Math.min(item.execution_percentage || 0, 100)" 
                                                          class="flex-1 h-2" />
                                                <span class="text-xs font-medium w-12" 
                                                      :class="getExecutionColor(item.execution_percentage || 0)">
                                                    {{ (item.execution_percentage || 0).toFixed(0) }}%
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <component :is="getVarianceIcon(item.variance_amount || 0)" 
                                                       :class="getVarianceColor(item.variance_amount || 0, item.variance_percentage || 0)"
                                                       class="h-4 w-4" />
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </TabsContent>

                        <TabsContent value="expenses" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ formatCurrency(expensesPerformance.total) }}
                                    </div>
                                    <p class="text-sm text-blue-600 font-medium">Presupuestado</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ formatCurrency(expensesPerformance.executed) }}
                                    </div>
                                    <p class="text-sm text-green-600 font-medium">Ejecutado</p>
                                </div>
                                <div class="text-center p-4 rounded-lg"
                                     :class="expensesPerformance.variance > 0 ? 'bg-red-50' : 'bg-green-50'">
                                    <div class="text-2xl font-bold"
                                         :class="expensesPerformance.variance > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ formatCurrency(Math.abs(expensesPerformance.variance)) }}
                                    </div>
                                    <p class="text-sm font-medium"
                                       :class="expensesPerformance.variance > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ expensesPerformance.variance > 0 ? 'Sobregiro' : 'Ahorro' }}
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
                                    <TableRow v-for="item in executionSummary.expenses" :key="item.id">
                                        <TableCell class="font-mono text-sm">{{ item.budgetItem?.account?.code || 'N/A' }}</TableCell>
                                        <TableCell class="font-medium">{{ item.budgetItem?.account?.name || 'N/A' }}</TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.budgeted_amount || 0) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono">
                                            {{ formatCurrency(item.actual_amount || 0) }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono"
                                                   :class="getVarianceColor(item.variance_amount || 0, item.variance_percentage || 0)">
                                            {{ formatCurrency(Math.abs(item.variance_amount || 0)) }}
                                            ({{ (item.variance_percentage || 0).toFixed(1) }}%)
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex items-center gap-2">
                                                <Progress :value="Math.min(item.execution_percentage || 0, 100)" 
                                                          class="flex-1 h-2" />
                                                <span class="text-xs font-medium w-12" 
                                                      :class="getExecutionColor(item.execution_percentage || 0)">
                                                    {{ (item.execution_percentage || 0).toFixed(0) }}%
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <component :is="getVarianceIcon(item.variance_amount || 0)" 
                                                       :class="getVarianceColor(item.variance_amount || 0, item.variance_percentage || 0)"
                                                       class="h-4 w-4" />
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