<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Eye, Save, TrendingDown, TrendingUp, AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Props {
    conjunto: any;
    availableYears: number[];
    closedYears: number[];
    closedMonths: Array<{ year: number; month: number }>;
}

const props = defineProps<Props>();

interface PreviewData {
    fiscal_year: number;
    period_start_date: string;
    period_end_date: string;
    total_income: number;
    total_expenses: number;
    net_result: number;
    is_profit: boolean;
    income_accounts_count: number;
    expense_accounts_count: number;
    income_details: Array<{ code: string; name: string; balance: number }>;
    expense_details: Array<{ code: string; name: string; balance: number }>;
}

const form = useForm({
    period_type: 'annual' as 'monthly' | 'annual',
    fiscal_year: props.availableYears[0] || new Date().getFullYear(),
    period_month: new Date().getMonth() + 1,
    closure_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const preview = ref<PreviewData | null>(null);
const isLoadingPreview = ref(false);
const previewError = ref<string | null>(null);

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Cierres Contables', href: route('accounting.closures.index') },
    { title: 'Nuevo Cierre', href: route('accounting.closures.create') },
];

const availableYearsToClose = computed(() => {
    if (form.period_type === 'annual') {
        return props.availableYears.filter(year => !props.closedYears.includes(year));
    }
    return props.availableYears;
});

const availableMonthsToClose = computed(() => {
    if (form.period_type === 'monthly') {
        const closedMonthsForYear = props.closedMonths
            .filter(cm => cm.year === form.fiscal_year)
            .map(cm => cm.month);

        const allMonths = [
            { value: 1, label: 'Enero' },
            { value: 2, label: 'Febrero' },
            { value: 3, label: 'Marzo' },
            { value: 4, label: 'Abril' },
            { value: 5, label: 'Mayo' },
            { value: 6, label: 'Junio' },
            { value: 7, label: 'Julio' },
            { value: 8, label: 'Agosto' },
            { value: 9, label: 'Septiembre' },
            { value: 10, label: 'Octubre' },
            { value: 11, label: 'Noviembre' },
            { value: 12, label: 'Diciembre' },
        ];

        return allMonths.filter(month => !closedMonthsForYear.includes(month.value));
    }
    return [];
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const loadPreview = async () => {
    isLoadingPreview.value = true;
    previewError.value = null;
    preview.value = null;

    try {
        const response = await axios.post(route('accounting.closures.preview'), {
            period_type: form.period_type,
            fiscal_year: form.fiscal_year,
            period_month: form.period_type === 'monthly' ? form.period_month : null,
        });

        if (response.data.success) {
            preview.value = response.data.data;
        } else {
            previewError.value = response.data.message || 'Error al cargar la vista previa';
        }
    } catch (error: any) {
        previewError.value = error.response?.data?.message || 'Error al cargar la vista previa';
    } finally {
        isLoadingPreview.value = false;
    }
};

const submit = () => {
    if (!preview.value) {
        alert('Debe cargar la vista previa antes de ejecutar el cierre');
        return;
    }

    const periodLabel = form.period_type === 'monthly'
        ? `${availableMonthsToClose.value.find(m => m.value === form.period_month)?.label} ${form.fiscal_year}`
        : `el año ${form.fiscal_year}`;

    if (confirm(`¿Está seguro de ejecutar el cierre contable para ${periodLabel}? Esta acción no se puede deshacer fácilmente.`)) {
        form.post(route('accounting.closures.store'));
    }
};
</script>

<template>

    <Head title="Nuevo Cierre Contable" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nuevo Cierre Contable</h1>
                    <p class="text-muted-foreground">
                        Ejecutar cierre contable {{ form.period_type === 'monthly' ? 'mensual' : 'anual' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('accounting.closures.index')">
                    <Button variant="outline" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Button>
                    </Link>
                </div>
            </div>

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Configuration Form -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Configuración del Cierre
                            </CardTitle>
                            <CardDescription>
                                Seleccione el período a cerrar y la fecha de cierre
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Period Type -->
                            <div class="space-y-2">
                                <Label for="period_type">Tipo de Cierre *</Label>
                                <Select v-model="form.period_type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el tipo de cierre" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Cierre Mensual</SelectItem>
                                        <SelectItem value="annual">Cierre Anual</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">
                                    Los cierres mensuales permiten cerrar períodos del año en curso
                                </p>
                                <p v-if="form.errors.period_type" class="text-sm text-red-600">
                                    {{ form.errors.period_type }}
                                </p>
                            </div>

                            <!-- Fiscal Year -->
                            <div class="space-y-2">
                                <Label for="fiscal_year">Año Fiscal *</Label>
                                <Select v-model="form.fiscal_year">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el año" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="year in availableYearsToClose" :key="year" :value="year">
                                            {{ year }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.period_type === 'annual' && availableYearsToClose.length === 0"
                                    class="text-sm text-amber-600">
                                    No hay años disponibles para cerrar. Todos los años recientes ya están cerrados.
                                </p>
                                <p v-if="form.errors.fiscal_year" class="text-sm text-red-600">
                                    {{ form.errors.fiscal_year }}
                                </p>
                            </div>

                            <!-- Period Month (only for monthly closures) -->
                            <div v-if="form.period_type === 'monthly'" class="space-y-2">
                                <Label for="period_month">Mes a Cerrar *</Label>
                                <Select v-model="form.period_month">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el mes" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="month in availableMonthsToClose" :key="month.value"
                                            :value="month.value">
                                            {{ month.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="availableMonthsToClose.length === 0" class="text-sm text-amber-600">
                                    No hay meses disponibles para cerrar en el año {{ form.fiscal_year }}.
                                </p>
                                <p v-if="form.errors.period_month" class="text-sm text-red-600">
                                    {{ form.errors.period_month }}
                                </p>
                            </div>

                            <!-- Closure Date -->
                            <div class="space-y-2">
                                <Label for="closure_date">Fecha de Cierre *</Label>
                                <Input id="closure_date" v-model="form.closure_date" type="date"
                                    :class="{ 'border-red-500': form.errors.closure_date }" />
                                <p class="text-xs text-muted-foreground">
                                    {{ form.period_type === 'monthly'
                                        ? 'Normalmente es el último día del mes'
                                        : 'Normalmente es el último día del año fiscal'
                                    }}
                                </p>
                                <p v-if="form.errors.closure_date" class="text-sm text-red-600">
                                    {{ form.errors.closure_date }}
                                </p>
                            </div>

                            <!-- Notes -->
                            <div class="space-y-2">
                                <Label for="notes">Notas</Label>
                                <Textarea id="notes" v-model="form.notes"
                                    placeholder="Observaciones sobre el cierre (opcional)" rows="3" />
                            </div>

                            <!-- Preview Button -->
                            <Button type="button" variant="outline" class="w-full gap-2"
                                :disabled="isLoadingPreview || availableYearsToClose.length === 0" @click="loadPreview">
                                <Eye class="h-4 w-4" />
                                {{ isLoadingPreview ? 'Cargando...' : 'Cargar Vista Previa' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Warning -->
                    <Alert variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>
                            <strong>Importante:</strong> El cierre contable es un proceso crítico que:
                            <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                                <li>Cerrará todas las cuentas de ingresos y gastos del {{ form.period_type === 'monthly'
                                    ? 'mes' : 'año' }}</li>
                                <li>Trasladará el resultado neto a patrimonio</li>
                                <li>Creará transacciones contables permanentes</li>
                                <li>No se puede deshacer fácilmente</li>
                            </ul>
                        </AlertDescription>
                    </Alert>
                </div>

                <!-- Preview Panel -->
                <div class="space-y-6">
                    <!-- Preview Error -->
                    <Alert v-if="previewError" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>
                            {{ previewError }}
                        </AlertDescription>
                    </Alert>

                    <!-- Preview Card -->
                    <Card v-if="preview">
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between">
                                <span>Vista Previa del Cierre</span>
                                <div class="flex items-center gap-1">
                                    <component :is="preview.is_profit ? TrendingUp : TrendingDown" class="h-5 w-5"
                                        :class="preview.is_profit ? 'text-green-600' : 'text-red-600'" />
                                    <span class="text-lg font-bold"
                                        :class="preview.is_profit ? 'text-green-600' : 'text-red-600'">
                                        {{ preview.is_profit ? 'Excedente' : 'Déficit' }}
                                    </span>
                                </div>
                            </CardTitle>
                            <CardDescription>
                                Resumen del año {{ preview.fiscal_year }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Summary -->
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Total Ingresos:</span>
                                    <span class="font-mono font-medium text-green-600">
                                        {{ formatCurrency(preview.total_income) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Total Gastos:</span>
                                    <span class="font-mono font-medium text-red-600">
                                        {{ formatCurrency(preview.total_expenses) }}
                                    </span>
                                </div>
                                <div class="border-t pt-3">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">Resultado Neto:</span>
                                        <span class="font-mono text-lg font-bold"
                                            :class="preview.is_profit ? 'text-green-600' : 'text-red-600'">
                                            {{ formatCurrency(Math.abs(preview.net_result)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Details -->
                            <div class="space-y-4">
                                <div>
                                    <h4 class="mb-2 text-sm font-medium">
                                        Cuentas de Ingresos ({{ preview.income_accounts_count }})
                                    </h4>
                                    <div class="max-h-40 overflow-y-auto rounded-md border">
                                        <Table>
                                            <TableBody>
                                                <TableRow v-for="account in preview.income_details" :key="account.code">
                                                    <TableCell class="py-2 text-xs">{{ account.code }}</TableCell>
                                                    <TableCell class="py-2 text-xs">{{ account.name }}</TableCell>
                                                    <TableCell class="py-2 text-right text-xs font-mono text-green-600">
                                                        {{ formatCurrency(account.balance) }}
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="mb-2 text-sm font-medium">
                                        Cuentas de Gastos ({{ preview.expense_accounts_count }})
                                    </h4>
                                    <div class="max-h-40 overflow-y-auto rounded-md border">
                                        <Table>
                                            <TableBody>
                                                <TableRow v-for="account in preview.expense_details"
                                                    :key="account.code">
                                                    <TableCell class="py-2 text-xs">{{ account.code }}</TableCell>
                                                    <TableCell class="py-2 text-xs">{{ account.name }}</TableCell>
                                                    <TableCell class="py-2 text-right text-xs font-mono text-red-600">
                                                        {{ formatCurrency(account.balance) }}
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <Button type="button" class="w-full gap-2" :disabled="form.processing" @click="submit">
                                <Save class="h-4 w-4" />
                                {{ form.processing ? 'Ejecutando Cierre...' : 'Ejecutar Cierre Contable' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Empty State -->
                    <Card v-else-if="!isLoadingPreview && !previewError">
                        <CardContent class="flex min-h-[400px] flex-col items-center justify-center py-12 text-center">
                            <Eye class="mb-4 h-12 w-12 text-muted-foreground opacity-50" />
                            <p class="text-muted-foreground">
                                Haga clic en "Cargar Vista Previa" para ver el resumen del cierre
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Loading State -->
                    <Card v-else-if="isLoadingPreview">
                        <CardContent class="flex min-h-[400px] flex-col items-center justify-center py-12 text-center">
                            <div
                                class="mb-4 h-12 w-12 animate-spin rounded-full border-4 border-primary border-t-transparent">
                            </div>
                            <p class="text-muted-foreground">Cargando vista previa...</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
