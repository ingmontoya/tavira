<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Download, Eye, MoreHorizontal, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface ExogenousReport {
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
    created_by?: {
        id: number;
        name: string;
    };
    export_file_path?: string;
    can_be_deleted: boolean;
}

const props = defineProps<{
    reports: {
        data: ExogenousReport[];
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
        current_page: number;
        last_page: number;
    };
    filters?: {
        fiscal_year?: string;
        report_type?: string;
        status?: string;
    };
    availableYears: number[];
}>();

const customFilters = ref({
    fiscal_year: props.filters?.fiscal_year || 'all',
    report_type: props.filters?.report_type || 'all',
    status: props.filters?.status || 'all',
});

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (customFilters.value.fiscal_year !== 'all') {
        params.fiscal_year = customFilters.value.fiscal_year;
    }
    if (customFilters.value.report_type !== 'all') {
        params.report_type = customFilters.value.report_type;
    }
    if (customFilters.value.status !== 'all') {
        params.status = customFilters.value.status;
    }

    router.get(route('accounting.exogenous-reports.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    customFilters.value = {
        fiscal_year: 'all',
        report_type: 'all',
        status: 'all',
    };
    router.get(route('accounting.exogenous-reports.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const hasActiveFilters = computed(() => {
    return Object.values(customFilters.value).some((value) => value !== 'all');
});

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
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
};

const viewReport = (reportId: number) => {
    router.visit(route('accounting.exogenous-reports.show', reportId));
};

const downloadReport = (reportId: number) => {
    window.location.href = route('accounting.exogenous-reports.download', reportId);
};

const deleteReport = (reportId: number) => {
    if (confirm('¿Está seguro de eliminar este reporte?')) {
        router.delete(route('accounting.exogenous-reports.destroy', reportId), {
            preserveScroll: true,
        });
    }
};

const goToPage = (url: string | null) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout>

        <Head title="Información Exógena DIAN" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Información Exógena DIAN</h1>
                    <p class="text-muted-foreground mt-1">
                        Genere y administre reportes de información exógena para la DIAN
                    </p>
                </div>
                <Button @click="router.visit(route('accounting.exogenous-reports.create'))">
                    <Plus class="mr-2 h-4 w-4" />
                    Generar Reporte
                </Button>
            </div>

            <!-- Filters -->
            <Card class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Año Fiscal</label>
                        <Select v-model="customFilters.fiscal_year">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los años" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los años</SelectItem>
                                <SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
                                    {{ year }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tipo de Reporte</label>
                        <Select v-model="customFilters.report_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los tipos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los tipos</SelectItem>
                                <SelectItem value="1001">Formato 1001</SelectItem>
                                <SelectItem value="1003">Formato 1003</SelectItem>
                                <SelectItem value="1005">Formato 1005</SelectItem>
                                <SelectItem value="1647">Formato 1647</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Estado</label>
                        <Select v-model="customFilters.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="draft">Borrador</SelectItem>
                                <SelectItem value="generated">Generado</SelectItem>
                                <SelectItem value="validated">Validado</SelectItem>
                                <SelectItem value="exported">Exportado</SelectItem>
                                <SelectItem value="submitted">Presentado</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end space-x-2">
                        <Button @click="applyFilters" class="flex-1">
                            Filtrar
                        </Button>
                        <Button v-if="hasActiveFilters" @click="clearFilters" variant="outline">
                            Limpiar
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Número</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Año Fiscal</TableHead>
                                <TableHead>Período</TableHead>
                                <TableHead class="text-right">Items</TableHead>
                                <TableHead class="text-right">Total Pagos</TableHead>
                                <TableHead class="text-right">Total Retenciones</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Generado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="reports.data.length === 0">
                                <TableCell colspan="10" class="text-center py-8 text-muted-foreground">
                                    No hay reportes generados. Haga clic en "Generar Reporte" para crear uno.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="report in reports.data" :key="report.id"
                                class="cursor-pointer hover:bg-muted/50" @click="viewReport(report.id)">
                                <TableCell class="font-medium">
                                    {{ report.report_number }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">{{ report.report_type_label }}</div>
                                </TableCell>
                                <TableCell>
                                    {{ report.fiscal_year }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        {{ formatDate(report.period_start) }} - {{ formatDate(report.period_end) }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    {{ report.total_items }}
                                </TableCell>
                                <TableCell class="text-right font-medium">
                                    {{ formatCurrency(report.total_amount) }}
                                </TableCell>
                                <TableCell class="text-right font-medium">
                                    {{ formatCurrency(report.total_withholding) }}
                                </TableCell>
                                <TableCell>
                                    <Badge :class="report.status_badge.class">
                                        {{ report.status_badge.text }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        {{ formatDate(report.generated_at) }}
                                    </div>
                                    <div v-if="report.created_by" class="text-xs text-muted-foreground">
                                        {{ report.created_by.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right" @click.stop>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" class="h-8 w-8 p-0">
                                                <span class="sr-only">Abrir menú</span>
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="viewReport(report.id)">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Ver Detalle
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="report.export_file_path"
                                                @click="downloadReport(report.id)">
                                                <Download class="mr-2 h-4 w-4" />
                                                Descargar
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator v-if="report.can_be_deleted" />
                                            <DropdownMenuItem v-if="report.can_be_deleted"
                                                @click="deleteReport(report.id)" class="text-destructive">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="reports.data.length > 0" class="flex items-center justify-between px-6 py-4 border-t">
                    <div class="text-sm text-muted-foreground">
                        Mostrando {{ reports.from }} a {{ reports.to }} de {{ reports.total }} reportes
                    </div>
                    <div class="flex space-x-2">
                        <Button variant="outline" size="sm" :disabled="!reports.prev_page_url"
                            @click="goToPage(reports.prev_page_url)">
                            Anterior
                        </Button>
                        <Button variant="outline" size="sm" :disabled="!reports.next_page_url"
                            @click="goToPage(reports.next_page_url)">
                            Siguiente
                        </Button>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
