<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ref } from 'vue';
import { ArrowLeft, FileDown, FileText } from 'lucide-vue-next';

interface Provider {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    address: string;
    email: string;
}

interface Expense {
    id: number;
    expense_number: string;
    expense_date: string;
    subtotal: number;
    tax_amount: number;
    tax_rate: number;
    category: string;
    tax_account_code: string;
    tax_account_name: string;
}

interface RetentionByType {
    account_code: string;
    account_name: string;
    count: number;
    total_retained: number;
}

interface Certificate {
    id: number;
    certificate_number: string;
    year: number;
    issued_at: string;
    total_withheld: number;
}

interface Props {
    provider: Provider;
    expenses: Expense[];
    retentionsByType: RetentionByType[];
    totalRetained: number;
    certificates: Certificate[];
    filters: {
        start_date: string | null;
        end_date: string | null;
    };
}

const props = defineProps<Props>();

const currentYear = new Date().getFullYear();
const certificateYear = ref(currentYear);

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO');
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-CO');
};

const goBack = () => {
    router.get('/retenciones', {
        start_date: props.filters.start_date,
        end_date: props.filters.end_date,
    });
};

const generateCertificateForm = useForm({
    provider_id: props.provider.id,
    year: currentYear,
});

const generateCertificate = () => {
    generateCertificateForm.year = certificateYear.value;
    generateCertificateForm.post('/retenciones/certificates/generate', {
        preserveScroll: true,
        onSuccess: () => {
            // Form will be reset on success
        },
    });
};

const downloadCertificate = (certificateId: number) => {
    window.location.href = `/retenciones/certificates/${certificateId}/download`;
};

const canGenerateCertificate = (year: number) => {
    return !props.certificates.some(cert => cert.year === year);
};
</script>

<template>
    <AppLayout>

        <Head :title="`Retenciones - ${provider.name}`" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" @click="goBack">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ provider.name }}</h1>
                        <p class="text-muted-foreground">
                            {{ provider.document_type }} {{ provider.document_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Provider Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Proveedor</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label class="text-muted-foreground">Nombre/Razón Social</Label>
                            <p class="font-medium">{{ provider.name }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Tipo de Documento</Label>
                            <p class="font-medium">{{ provider.document_type || '-' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Número de Documento</Label>
                            <p class="font-medium">{{ provider.document_number || '-' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Dirección</Label>
                            <p class="font-medium">{{ provider.address || '-' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Email</Label>
                            <p class="font-medium">{{ provider.email || '-' }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Total Retenido</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(totalRetained) }}</div>
                        <p class="text-xs text-muted-foreground">En el período seleccionado</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Gastos con Retención</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ expenses.length }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Tipos de Retención</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ retentionsByType.length }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Retentions by Type -->
            <Card>
                <CardHeader>
                    <CardTitle>Retenciones por Tipo</CardTitle>
                    <CardDescription>Resumen de retenciones agrupadas por cuenta contable</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="retention in retentionsByType" :key="retention.account_code"
                            class="flex items-center justify-between border-b pb-3 last:border-0">
                            <div>
                                <p class="font-medium">{{ retention.account_code }} - {{ retention.account_name }}</p>
                                <p class="text-sm text-muted-foreground">{{ retention.count }} gastos</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold">{{ formatCurrency(retention.total_retained) }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Expenses Detail -->
            <Card>
                <CardHeader>
                    <CardTitle>Detalle de Gastos con Retención</CardTitle>
                    <CardDescription>Listado completo de gastos con retención en la fuente</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nro. Gasto</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Tipo Retención</TableHead>
                                <TableHead class="text-right">Base</TableHead>
                                <TableHead class="text-right">Tasa</TableHead>
                                <TableHead class="text-right">Retención</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="expense in expenses" :key="expense.id">
                                <TableCell class="font-medium">{{ expense.expense_number }}</TableCell>
                                <TableCell>{{ formatDate(expense.expense_date) }}</TableCell>
                                <TableCell>{{ expense.category || '-' }}</TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p class="font-medium">{{ expense.tax_account_code }}</p>
                                        <p class="text-muted-foreground">{{ expense.tax_account_name }}</p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">{{ formatCurrency(expense.subtotal) }}</TableCell>
                                <TableCell class="text-right">{{ expense.tax_rate }}%</TableCell>
                                <TableCell class="text-right font-medium">
                                    {{ formatCurrency(expense.tax_amount) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Certificates Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Generate Certificate -->
                <Card>
                    <CardHeader>
                        <CardTitle>Generar Certificado</CardTitle>
                        <CardDescription>
                            Genera un certificado de retención en la fuente para este proveedor
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="generateCertificate" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="year">Año del Certificado</Label>
                                <Input id="year" v-model.number="certificateYear" type="number" :min="2020"
                                    :max="currentYear" />
                            </div>
                            <Button type="submit" class="w-full"
                                :disabled="generateCertificateForm.processing || !canGenerateCertificate(certificateYear)">
                                <FileText class="mr-2 h-4 w-4" />
                                {{
                                    canGenerateCertificate(certificateYear)
                                        ? 'Generar Certificado'
                                        : 'Certificado Ya Existe'
                                }}
                            </Button>
                            <p v-if="!canGenerateCertificate(certificateYear)" class="text-sm text-muted-foreground">
                                Ya existe un certificado para el año {{ certificateYear }}
                            </p>
                        </form>
                    </CardContent>
                </Card>

                <!-- Existing Certificates -->
                <Card>
                    <CardHeader>
                        <CardTitle>Certificados Generados</CardTitle>
                        <CardDescription>Certificados disponibles para descargar</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="certificates.length > 0" class="space-y-3">
                            <div v-for="certificate in certificates" :key="certificate.id"
                                class="flex items-center justify-between border rounded-lg p-3">
                                <div>
                                    <p class="font-medium">{{ certificate.certificate_number }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Año {{ certificate.year }} - {{ formatCurrency(certificate.total_withheld) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Emitido: {{ formatDateTime(certificate.issued_at) }}
                                    </p>
                                </div>
                                <Button variant="outline" size="sm" @click="downloadCertificate(certificate.id)">
                                    <FileDown class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center">
                            <p class="text-muted-foreground">No hay certificados generados</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
