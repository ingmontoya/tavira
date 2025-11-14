<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Download,
    FileText,
    Send,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import axios from 'axios';

interface ReportItem {
    id: number;
    third_party_document_type: string;
    third_party_document_number: string;
    third_party_verification_digit?: string;
    third_party_name: string;
    third_party_city?: string;
    concept_code?: string;
    concept_name: string;
    payment_amount: number;
    withholding_amount: number;
    tax_base: number;
    withholding_rate?: number;
    provider?: {
        id: number;
        name: string;
    };
}

interface ExogenousReport {
    id: number;
    report_number: string;
    report_type: string;
    report_type_label: string;
    report_name: string;
    fiscal_year: number;
    period_start: string;
    period_end: string;
    status: string;
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    total_items: number;
    total_amount: number;
    total_withholding: number;
    generated_at?: string;
    validated_at?: string;
    exported_at?: string;
    submitted_at?: string;
    export_file_path?: string;
    export_format?: string;
    notes?: string;
    can_be_validated: boolean;
    can_be_exported: boolean;
    can_be_deleted: boolean;
    created_by?: {
        id: number;
        name: string;
    };
    validated_by?: {
        id: number;
        name: string;
    };
    exported_by?: {
        id: number;
        name: string;
    };
    items: ReportItem[];
}

const props = defineProps<{
    report: ExogenousReport;
    configuration?: any;
}>();

const showExportDialog = ref(false);
const selectedExportFormat = ref('xml');
const isExporting = ref(false);
const exportError = ref<string | null>(null);

const isValidating = ref(false);
const isSubmitting = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string | null) => {
    if (!date) return '-';
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
};

const formatPercentage = (value: number | null | undefined) => {
    if (value === null || value === undefined) return '-';
    return `${Number(value).toFixed(2)}%`;
};

const validateReport = async () => {
    isValidating.value = true;
    router.post(
        route('accounting.exogenous-reports.validate', props.report.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isValidating.value = false;
            },
        }
    );
};

const exportReport = async () => {
    if (!selectedExportFormat.value) return;

    isExporting.value = true;
    exportError.value = null;

    try {
        const response = await axios.post(
            route('accounting.exogenous-reports.export', props.report.id),
            { format: selectedExportFormat.value }
        );

        if (response.data.success) {
            showExportDialog.value = false;
            router.reload({ preserveScroll: true });
        } else {
            exportError.value = response.data.message || 'Error al exportar el reporte';
        }
    } catch (error: any) {
        exportError.value = error.response?.data?.message || 'Error al exportar el reporte';
    } finally {
        isExporting.value = false;
    }
};

const downloadReport = () => {
    window.location.href = route('accounting.exogenous-reports.download', props.report.id);
};

const markAsSubmitted = () => {
    if (confirm('¿Confirma que este reporte ha sido presentado a la DIAN?')) {
        isSubmitting.value = true;
        router.post(
            route('accounting.exogenous-reports.mark-submitted', props.report.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isSubmitting.value = false;
                },
            }
        );
    }
};

const netPayment = computed(() => {
    return props.report.total_amount - props.report.total_withholding;
});
</script>

<template>
    <AppLayout>

        <Head :title="`Reporte ${report.report_number}`" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Button variant="ghost" size="icon"
                        @click="router.visit(route('accounting.exogenous-reports.index'))">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <div class="flex items-center space-x-3">
                            <h1 class="text-3xl font-bold tracking-tight">{{ report.report_number }}</h1>
                            <Badge :class="report.status_badge.class">
                                {{ report.status_badge.text }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground mt-1">{{ report.report_type_label }}</p>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <Button v-if="report.can_be_validated" @click="validateReport" :disabled="isValidating"
                        variant="outline">
                        <Loader2 v-if="isValidating" class="mr-2 h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="mr-2 h-4 w-4" />
                        Validar
                    </Button>

                    <Button v-if="report.can_be_exported" @click="showExportDialog = true" variant="outline">
                        <FileText class="mr-2 h-4 w-4" />
                        Exportar
                    </Button>

                    <Button v-if="report.export_file_path" @click="downloadReport">
                        <Download class="mr-2 h-4 w-4" />
                        Descargar
                    </Button>

                    <Button v-if="report.status === 'exported'" @click="markAsSubmitted" :disabled="isSubmitting">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        <Send v-else class="mr-2 h-4 w-4" />
                        Marcar Presentado
                    </Button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardDescription>Items Reportados</CardDescription>
                        <CardTitle class="text-3xl">{{ report.total_items }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardDescription>Total Pagos</CardDescription>
                        <CardTitle class="text-2xl">{{ formatCurrency(report.total_amount) }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardDescription>Total Retenciones</CardDescription>
                        <CardTitle class="text-2xl">{{ formatCurrency(report.total_withholding) }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardDescription>Pago Neto</CardDescription>
                        <CardTitle class="text-2xl">{{ formatCurrency(netPayment) }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Report Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Reporte</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-muted-foreground">Año Fiscal</p>
                                <p class="font-medium">{{ report.fiscal_year }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Período</p>
                                <p class="font-medium">
                                    {{ formatDate(report.period_start) }} - {{ formatDate(report.period_end) }}
                                </p>
                            </div>
                            <div v-if="report.generated_at">
                                <p class="text-sm text-muted-foreground">Generado</p>
                                <p class="font-medium">{{ formatDate(report.generated_at) }}</p>
                                <p v-if="report.created_by" class="text-sm text-muted-foreground">
                                    Por: {{ report.created_by.name }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-if="report.validated_at">
                                <p class="text-sm text-muted-foreground">Validado</p>
                                <p class="font-medium">{{ formatDate(report.validated_at) }}</p>
                                <p v-if="report.validated_by" class="text-sm text-muted-foreground">
                                    Por: {{ report.validated_by.name }}
                                </p>
                            </div>
                            <div v-if="report.exported_at">
                                <p class="text-sm text-muted-foreground">Exportado</p>
                                <p class="font-medium">{{ formatDate(report.exported_at) }}</p>
                                <p v-if="report.export_format" class="text-sm text-muted-foreground">
                                    Formato: {{ report.export_format.toUpperCase() }}
                                </p>
                            </div>
                            <div v-if="report.submitted_at">
                                <p class="text-sm text-muted-foreground">Presentado a DIAN</p>
                                <p class="font-medium">{{ formatDate(report.submitted_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="report.notes" class="mt-6 pt-6 border-t">
                        <p class="text-sm text-muted-foreground">Notas</p>
                        <p class="mt-2 whitespace-pre-wrap">{{ report.notes }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Items Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Items del Reporte ({{ report.total_items }})</CardTitle>
                    <CardDescription>Detalle de terceros incluidos en el reporte</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tipo Doc</TableHead>
                                    <TableHead>Documento</TableHead>
                                    <TableHead>Nombre/Razón Social</TableHead>
                                    <TableHead>Ciudad</TableHead>
                                    <TableHead>Concepto</TableHead>
                                    <TableHead class="text-right">Base Gravable</TableHead>
                                    <TableHead class="text-right">Tarifa %</TableHead>
                                    <TableHead class="text-right">Retención</TableHead>
                                    <TableHead class="text-right">Valor Pago</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="report.items.length === 0">
                                    <TableCell colspan="9" class="text-center py-8 text-muted-foreground">
                                        No hay items en este reporte
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="item in report.items" :key="item.id">
                                    <TableCell>{{ item.third_party_document_type }}</TableCell>
                                    <TableCell class="font-medium">
                                        {{ item.third_party_document_number }}
                                        <span v-if="item.third_party_verification_digit">
                                            -{{ item.third_party_verification_digit }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="max-w-xs truncate" :title="item.third_party_name">
                                            {{ item.third_party_name }}
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ item.third_party_city || '-' }}</TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ item.concept_name }}
                                            <span v-if="item.concept_code" class="text-muted-foreground">
                                                ({{ item.concept_code }})
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ formatCurrency(item.tax_base) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ formatPercentage(item.withholding_rate) }}
                                    </TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ formatCurrency(item.withholding_amount) }}
                                    </TableCell>
                                    <TableCell class="text-right font-bold">
                                        {{ formatCurrency(item.payment_amount) }}
                                    </TableCell>
                                </TableRow>
                                <!-- Totals Row -->
                                <TableRow v-if="report.items.length > 0" class="bg-muted/50 font-bold">
                                    <TableCell colspan="7" class="text-right">TOTALES</TableCell>
                                    <TableCell class="text-right">
                                        {{ formatCurrency(report.total_withholding) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ formatCurrency(report.total_amount) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Export Dialog -->
        <Dialog v-model:open="showExportDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Exportar Reporte</DialogTitle>
                    <DialogDescription>
                        Seleccione el formato de exportación para el reporte
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Formato de Exportación</label>
                        <Select v-model="selectedExportFormat">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="xml">
                                    <div class="flex flex-col">
                                        <span class="font-medium">XML</span>
                                        <span class="text-xs text-muted-foreground">
                                            Formato estándar para presentación DIAN
                                        </span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="txt">
                                    <div class="flex flex-col">
                                        <span class="font-medium">TXT (Pipe-delimited)</span>
                                        <span class="text-xs text-muted-foreground">
                                            Formato de texto plano alternativo
                                        </span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="excel">
                                    <div class="flex flex-col">
                                        <span class="font-medium">Excel (CSV)</span>
                                        <span class="text-xs text-muted-foreground">
                                            Para revisión interna (no válido para DIAN)
                                        </span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <Alert v-if="exportError" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>{{ exportError }}</AlertDescription>
                    </Alert>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showExportDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="exportReport" :disabled="isExporting">
                        <Loader2 v-if="isExporting" class="mr-2 h-4 w-4 animate-spin" />
                        <Download v-else class="mr-2 h-4 w-4" />
                        Exportar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
