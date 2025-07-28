<script setup lang="ts">
import ToastContainer from '@/components/ToastContainer.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import ValidationErrors from '@/components/ValidationErrors.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle, CreditCard, Receipt, Settings, TrendingUp, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Stats {
    pending_invoices: number;
    monthly_collection: number;
    delinquent_apartments: number;
    active_concepts: number;
}

interface Props {
    stats: Stats;
}

const props = defineProps<Props>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Toast notifications
const { success, error } = useToast();

// Watch for flash messages and convert to toasts
watch(flashSuccess, (message) => {
    if (message) {
        success(message, 'Éxito');
    }
});

watch(flashError, (message) => {
    if (message) {
        error(message, 'Error');
    }
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Pagos',
        href: '/payments',
    },
];

// Modal state
const isModalOpen = ref(false);
const selectedYear = ref<string>(new Date().getFullYear().toString());
const selectedMonth = ref<string>((new Date().getMonth() + 1).toString());
const isGenerating = ref(false);

// Computed properties
const canGenerateInvoices = computed(() => {
    return props.stats.active_concepts > 0;
});

const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 3 }, (_, i) => (currentYear - 1 + i).toString());
});

const monthOptions = [
    { value: '1', label: 'Enero' },
    { value: '2', label: 'Febrero' },
    { value: '3', label: 'Marzo' },
    { value: '4', label: 'Abril' },
    { value: '5', label: 'Mayo' },
    { value: '6', label: 'Junio' },
    { value: '7', label: 'Julio' },
    { value: '8', label: 'Agosto' },
    { value: '9', label: 'Septiembre' },
    { value: '10', label: 'Octubre' },
    { value: '11', label: 'Noviembre' },
    { value: '12', label: 'Diciembre' },
];

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const generateMonthlyInvoices = async () => {
    if (!canGenerateInvoices.value) return;

    isGenerating.value = true;

    try {
        await router.post(
            '/invoices/generate-monthly',
            {
                year: parseInt(selectedYear.value),
                month: parseInt(selectedMonth.value),
            },
            {
                onSuccess: (page) => {
                    console.log('Invoice generation successful:', page);
                    isModalOpen.value = false;
                    // The success message will be handled by the flash message watcher
                    // but we can also show an immediate toast for better UX
                    success('Facturas generadas exitosamente', 'Éxito');
                },
                onError: (errors) => {
                    console.error('Error generating invoices:', errors);
                    if (errors.error) {
                        error(errors.error, 'Error');
                    } else if (Object.keys(errors).length > 0) {
                        const firstError = Object.values(errors)[0];
                        error(Array.isArray(firstError) ? firstError[0] : firstError, 'Error de validación');
                    } else {
                        error('Error desconocido al generar las facturas', 'Error');
                    }
                },
                onFinish: () => {
                    isGenerating.value = false;
                },
            },
        );
    } catch (err) {
        console.error('Unexpected error:', err);
        error('Error inesperado al generar las facturas', 'Error');
        isGenerating.value = false;
    }
};
</script>

<template>
    <Head title="Gestión de Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Flash Messages -->
            <Alert v-if="flashSuccess" class="mb-4">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flashSuccess }}</AlertDescription>
            </Alert>

            <Alert v-if="flashError" variant="destructive" class="mb-4">
                <XCircle class="h-4 w-4" />
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <!-- Validation Errors -->
            <ValidationErrors :errors="errors" />

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Gestión de Pagos</h1>
                    <p class="text-muted-foreground">Administra facturas, conceptos de pago y reportes financieros del conjunto</p>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Facturas Pendientes</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ props.stats.pending_invoices }}</div>
                        <p class="text-xs text-muted-foreground">Facturas por cobrar</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Recaudo Mensual</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(props.stats.monthly_collection) }}</div>
                        <p class="text-xs text-muted-foreground">Ingresos del mes actual</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Apartamentos en Mora</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ props.stats.delinquent_apartments }}</div>
                        <p class="text-xs text-muted-foreground">Con pagos vencidos</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Conceptos Activos</CardTitle>
                        <Settings class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.active_concepts }}</div>
                        <p class="text-xs text-muted-foreground">Conceptos de pago configurados</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Actions -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Facturas -->
                <Card class="cursor-pointer transition-all hover:shadow-md">
                    <CardHeader>
                        <div class="flex items-center space-x-2">
                            <Receipt class="h-5 w-5 text-blue-600" />
                            <CardTitle class="text-lg">Facturas</CardTitle>
                        </div>
                        <CardDescription> Gestiona las facturas de administración, visualiza estados de pago y registra pagos </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <Button asChild class="w-full" variant="default">
                                <Link href="/invoices">Ver Todas las Facturas</Link>
                            </Button>
                            <div class="flex space-x-2">
                                <Button asChild class="flex-1" variant="outline" size="sm">
                                    <Link href="/invoices/create">Crear Factura</Link>
                                </Button>
                                <Button asChild class="flex-1" variant="outline" size="sm">
                                    <Link href="/invoices?status=pending">Pendientes</Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Conceptos de Pago -->
                <Card class="cursor-pointer transition-all hover:shadow-md">
                    <CardHeader>
                        <div class="flex items-center space-x-2">
                            <Settings class="h-5 w-5 text-green-600" />
                            <CardTitle class="text-lg">Conceptos de Pago</CardTitle>
                        </div>
                        <CardDescription> Configura los conceptos de facturación: administración, sanciones, parqueaderos </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <Button asChild class="w-full" variant="default">
                                <Link href="/payment-concepts">Gestionar Conceptos</Link>
                            </Button>
                            <div class="flex space-x-2">
                                <Button asChild class="flex-1" variant="outline" size="sm">
                                    <Link href="/payment-concepts/create">Nuevo Concepto</Link>
                                </Button>
                                <Button asChild class="flex-1" variant="outline" size="sm">
                                    <Link href="/payment-concepts?type=common_expense">Administración</Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Generación Masiva -->
                <Card class="cursor-pointer transition-all hover:shadow-md">
                    <CardHeader>
                        <div class="flex items-center space-x-2">
                            <CreditCard class="h-5 w-5 text-purple-600" />
                            <CardTitle class="text-lg">Facturación Masiva</CardTitle>
                        </div>
                        <CardDescription> Genera facturas mensuales automáticamente para todos los apartamentos ocupados </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <Dialog v-model:open="isModalOpen">
                                <DialogTrigger as-child>
                                    <Button class="w-full" variant="default" :disabled="!canGenerateInvoices"> Generar Facturas Mensual </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Generar Facturas Mensuales</DialogTitle>
                                        <DialogDescription> Selecciona el año y mes para generar las facturas automáticamente. </DialogDescription>
                                    </DialogHeader>
                                    <div class="grid gap-4 py-4">
                                        <div class="grid gap-2">
                                            <Label for="year">Año</Label>
                                            <Select v-model="selectedYear">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecciona el año" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="year in yearOptions" :key="year" :value="year">
                                                        {{ year }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="month">Mes</Label>
                                            <Select v-model="selectedMonth">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecciona el mes" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="month in monthOptions" :key="month.value" :value="month.value">
                                                        {{ month.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isModalOpen = false" :disabled="isGenerating">
                                            Cancelar
                                        </Button>
                                        <Button type="button" @click="generateMonthlyInvoices" :disabled="isGenerating">
                                            {{ isGenerating ? 'Generando...' : 'Generar Facturas' }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            <div class="text-xs text-muted-foreground">
                                <p>• Facturas basadas en conceptos recurrentes</p>
                                <p>• Solo apartamentos ocupados</p>
                                <p>• Verificación de duplicados</p>
                                <p v-if="!canGenerateInvoices" class="font-medium text-red-500">• Requiere conceptos de pago activos</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle>Actividad Reciente</CardTitle>
                    <CardDescription> Últimas facturas creadas y pagos registrados </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="py-8 text-center text-muted-foreground">
                        <Receipt class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p>No hay actividad reciente</p>
                        <p class="text-sm">Las facturas y pagos aparecerán aquí cuando se generen</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Toast Notifications -->
        <ToastContainer />
    </AppLayout>
</template>
