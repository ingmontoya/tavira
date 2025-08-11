<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, BookOpen, Calculator, DollarSign, FileText, Scale, TrendingUp } from 'lucide-vue-next';

interface AvailableReport {
    name: string;
    description: string;
    icon: string;
}

interface AvailableReports {
    [key: string]: AvailableReport;
}

const props = defineProps<{
    availableReports: AvailableReports;
}>();

const getIcon = (iconName: string) => {
    const icons = {
        scale: Scale,
        trending: TrendingUp,
        calculator: Calculator,
        book: BookOpen,
        chart: BarChart3,
        dollar: DollarSign,
        file: FileText,
    };
    return icons[iconName] || FileText;
};

const navigateToReport = (reportKey: string) => {
    const today = new Date().toISOString().split('T')[0];
    const currentMonth = new Date();
    const startOfMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1).toISOString().split('T')[0];
    
    // Build URL with appropriate default parameters based on report type
    let url = `/accounting/reports/${reportKey}`;
    const params = new URLSearchParams();
    
    switch (reportKey) {
        case 'balance-sheet':
        case 'trial-balance':
            params.append('as_of_date', today);
            break;
        case 'income-statement':
        case 'cash-flow':
            params.append('start_date', startOfMonth);
            params.append('end_date', today);
            break;
        case 'general-ledger':
            // General ledger will use the first available account as default
            params.append('start_date', startOfMonth);
            params.append('end_date', today);
            break;
        case 'budget-execution':
            params.append('period_month', (currentMonth.getMonth() + 1).toString());
            params.append('period_year', currentMonth.getFullYear().toString());
            break;
    }
    
    if (params.toString()) {
        url += `?${params.toString()}`;
    }
    
    router.visit(url);
};

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
];
</script>

<template>
    <Head title="Reportes Contables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">Reportes Contables</h1>
                <p class="text-muted-foreground">
                    Accede a todos los reportes financieros y contables de la propiedad horizontal
                </p>
            </div>

            <!-- Reports Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="(report, key) in props.availableReports"
                    :key="key"
                    class="cursor-pointer transition-all hover:shadow-lg hover:scale-105"
                    @click="navigateToReport(key)"
                >
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div class="space-y-1">
                            <CardTitle class="text-lg">{{ report.name }}</CardTitle>
                            <CardDescription class="text-sm">{{ report.description }}</CardDescription>
                        </div>
                        <div class="rounded-md bg-primary/10 p-3">
                            <component :is="getIcon(report.icon)" class="h-6 w-6 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Button variant="ghost" class="w-full justify-start p-0 h-auto font-normal">
                            <span class="text-sm text-muted-foreground">Ver reporte →</span>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Reportes Básicos
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm text-muted-foreground">
                        <p>• Balance General y Estado de Resultados</p>
                        <p>• Balance de Prueba con todos los saldos</p>
                        <p>• Libro Mayor detallado por cuenta</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Análisis Financiero
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm text-muted-foreground">
                        <p>• Flujo de Caja y movimientos de efectivo</p>
                        <p>• Ejecución Presupuestal vs Real</p>
                        <p>• Análisis comparativo de períodos</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>