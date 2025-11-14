<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, FileText, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

interface Configuration {
    report_type: string;
    report_type_label: string;
    is_configured: boolean;
    minimum_reporting_amount: number;
}

interface ReportType {
    code: string;
    label: string;
    description: string;
}

interface PreviewData {
    total_providers: number;
    included_providers: number;
    excluded_providers: number;
    total_payment_amount: number;
    total_withholding_amount: number;
    threshold: number;
    period_start: string;
    period_end: string;
    preview_items: Array<{
        provider: {
            name: string;
            document_number: string;
        };
        payment_amount: number;
        withholding_amount: number;
        transaction_count: number;
    }>;
}

const props = defineProps<{
    availableYears: number[];
    configurations: Configuration[];
    reportTypes: ReportType[];
}>();

const form = useForm({
    report_type: '',
    fiscal_year: new Date().getFullYear(),
    period_start: '',
    period_end: '',
    notes: '',
});

const isLoadingPreview = ref(false);
const previewData = ref<PreviewData | null>(null);
const previewError = ref<string | null>(null);

const selectedReportType = computed(() => {
    return props.reportTypes.find((type) => type.code === form.report_type);
});

const selectedConfiguration = computed(() => {
    return props.configurations.find((config) => config.report_type === form.report_type);
});

const isFormValid = computed(() => {
    return form.report_type && form.fiscal_year && form.period_start && form.period_end;
});

// Set default period dates when year changes
watch(() => form.fiscal_year, (newYear) => {
    if (newYear) {
        form.period_start = `${newYear}-01-01`;
        form.period_end = `${newYear}-12-31`;
        previewData.value = null; // Clear preview when year changes
    }
});

// Load preview when form is valid
const loadPreview = async () => {
    if (!isFormValid.value) return;

    isLoadingPreview.value = true;
    previewError.value = null;
    previewData.value = null;

    try {
        const response = await axios.post(route('accounting.exogenous-reports.preview'), {
            report_type: form.report_type,
            fiscal_year: form.fiscal_year,
            period_start: form.period_start,
            period_end: form.period_end,
        });

        if (response.data.success) {
            previewData.value = response.data.preview;
        } else {
            previewError.value = response.data.message || 'Error al cargar la vista previa';
        }
    } catch (error: any) {
        previewError.value = error.response?.data?.message || 'Error al cargar la vista previa';
    } finally {
        isLoadingPreview.value = false;
    }
};

watch([() => form.report_type, () => form.fiscal_year, () => form.period_start, () => form.period_end], () => {
    if (isFormValid.value) {
        loadPreview();
    }
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const submit = () => {
    form.post(route('accounting.exogenous-reports.store'));
};
</script>

<template>
    <AppLayout>

        <Head title="Generar Reporte Exógeno" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Button variant="ghost" size="icon"
                        @click="router.visit(route('accounting.exogenous-reports.index'))">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Generar Reporte Exógeno</h1>
                        <p class="text-muted-foreground mt-1">
                            Configure y genere un nuevo reporte de información exógena para la DIAN
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración del Reporte</CardTitle>
                        <CardDescription>
                            Seleccione el tipo de reporte y el período fiscal
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Report Type -->
                        <div class="space-y-2">
                            <Label for="report_type">Tipo de Reporte *</Label>
                            <Select v-model="form.report_type" required>
                                <SelectTrigger id="report_type">
                                    <SelectValue placeholder="Seleccione un tipo de reporte" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in reportTypes" :key="type.code" :value="type.code">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ type.label }}</span>
                                            <span class="text-xs text-muted-foreground">{{ type.description }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.report_type" class="text-sm text-destructive">
                                {{ form.errors.report_type }}
                            </p>
                        </div>

                        <!-- Configuration Warning -->
                        <Alert v-if="selectedConfiguration && !selectedConfiguration.is_configured"
                            variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                Este tipo de reporte no está completamente configurado. Verifique la información de la
                                entidad y responsable en configuración.
                            </AlertDescription>
                        </Alert>

                        <!-- Fiscal Year -->
                        <div class="space-y-2">
                            <Label for="fiscal_year">Año Fiscal *</Label>
                            <Select v-model="form.fiscal_year" required>
                                <SelectTrigger id="fiscal_year">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.fiscal_year" class="text-sm text-destructive">
                                {{ form.errors.fiscal_year }}
                            </p>
                        </div>

                        <!-- Period Start -->
                        <div class="space-y-2">
                            <Label for="period_start">Fecha Inicio *</Label>
                            <Input id="period_start" v-model="form.period_start" type="date" required />
                            <p v-if="form.errors.period_start" class="text-sm text-destructive">
                                {{ form.errors.period_start }}
                            </p>
                        </div>

                        <!-- Period End -->
                        <div class="space-y-2">
                            <Label for="period_end">Fecha Fin *</Label>
                            <Input id="period_end" v-model="form.period_end" type="date" required />
                            <p v-if="form.errors.period_end" class="text-sm text-destructive">
                                {{ form.errors.period_end }}
                            </p>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notas (opcional)</Label>
                            <Textarea id="notes" v-model="form.notes"
                                placeholder="Agregue notas o comentarios sobre este reporte..." rows="3" />
                        </div>

                        <!-- Submit Button -->
                        <div class="flex space-x-2">
                            <Button type="button" @click="submit" :disabled="!isFormValid || form.processing"
                                class="flex-1">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <FileText v-else class="mr-2 h-4 w-4" />
                                Generar Reporte
                            </Button>
                            <Button type="button" variant="outline"
                                @click="router.visit(route('accounting.exogenous-reports.index'))">
                                Cancelar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Preview Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Vista Previa</CardTitle>
                        <CardDescription>
                            Resumen de lo que se incluirá en el reporte
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <!-- Loading State -->
                        <div v-if="isLoadingPreview" class="flex flex-col items-center justify-center py-12 space-y-4">
                            <Loader2 class="h-8 w-8 animate-spin text-primary" />
                            <p class="text-sm text-muted-foreground">Cargando vista previa...</p>
                        </div>

                        <!-- Error State -->
                        <Alert v-else-if="previewError" variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>{{ previewError }}</AlertDescription>
                        </Alert>

                        <!-- Empty State -->
                        <div v-else-if="!previewData"
                            class="flex flex-col items-center justify-center py-12 text-center">
                            <FileText class="h-12 w-12 text-muted-foreground/50 mb-4" />
                            <p class="text-sm text-muted-foreground">
                                Complete el formulario para ver la vista previa
                            </p>
                        </div>

                        <!-- Preview Data -->
                        <div v-else class="space-y-6">
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-muted-foreground">Total Proveedores</p>
                                    <p class="text-2xl font-bold">{{ previewData.total_providers }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm text-muted-foreground">Incluidos</p>
                                    <p class="text-2xl font-bold text-green-600">{{ previewData.included_providers }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm text-muted-foreground">Total Pagos</p>
                                    <p class="text-lg font-semibold">
                                        {{ formatCurrency(previewData.total_payment_amount) }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm text-muted-foreground">Total Retenciones</p>
                                    <p class="text-lg font-semibold">
                                        {{ formatCurrency(previewData.total_withholding_amount) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Threshold Info -->
                            <Alert>
                                <AlertDescription>
                                    <strong>Umbral de reporte:</strong> {{ formatCurrency(previewData.threshold) }}<br>
                                    Se excluirán {{ previewData.excluded_providers }} proveedores por debajo del umbral.
                                </AlertDescription>
                            </Alert>

                            <!-- Preview Items -->
                            <div v-if="previewData.preview_items.length > 0" class="space-y-2">
                                <h4 class="text-sm font-medium">Primeros 10 Proveedores</h4>
                                <div class="space-y-2">
                                    <div v-for="(item, index) in previewData.preview_items" :key="index"
                                        class="flex items-center justify-between p-3 border rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">{{ item.provider.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                NIT: {{ item.provider.document_number }} • {{ item.transaction_count }}
                                                transacciones
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold">
                                                {{ formatCurrency(item.payment_amount) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                Ret: {{ formatCurrency(item.withholding_amount) }}
                                            </p>
                                        </div>
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
