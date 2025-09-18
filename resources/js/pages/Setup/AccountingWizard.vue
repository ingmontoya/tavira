<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle, Home, Settings, FileText, Calculator, Zap } from 'lucide-vue-next';
import { computed } from 'vue';

interface SetupStatus {
    has_apartments: boolean;
    has_chart_of_accounts: boolean;
    has_payment_concepts: boolean;
    has_accounting_mappings: boolean;
    apartments_count: number;
    chart_accounts_count: number;
    payment_concepts_count: number;
    mappings_count: number;
}

const props = defineProps<{
    setup_status: SetupStatus;
}>();

const steps = [
    {
        id: 'apartments',
        title: 'Configurar Apartamentos',
        description: 'Configure los apartamentos del conjunto residencial',
        icon: Home,
        route: '/apartments',
        completed: props.setup_status.has_apartments,
        count: props.setup_status.apartments_count,
        action: 'Ir a Apartamentos',
    },
    {
        id: 'chart_of_accounts',
        title: 'Plan de Cuentas Contable',
        description: 'Inicialice el plan de cuentas contable colombiano',
        icon: FileText,
        route: '/accounting/chart-of-accounts',
        completed: props.setup_status.has_chart_of_accounts,
        count: props.setup_status.chart_accounts_count,
        action: 'Configurar Plan de Cuentas',
    },
    {
        id: 'payment_concepts',
        title: 'Conceptos de Pago',
        description: 'Cree los conceptos de pago (administración, multas, etc.)',
        icon: Calculator,
        route: '/settings/payment-concepts',
        completed: props.setup_status.has_payment_concepts,
        count: props.setup_status.payment_concepts_count,
        action: 'Configurar Conceptos',
    },
    {
        id: 'accounting_mappings',
        title: 'Mapeo Contable',
        description: 'Configure el mapeo entre conceptos de pago y cuentas contables',
        icon: Settings,
        route: '/settings/payment-concept-mapping',
        completed: props.setup_status.has_accounting_mappings,
        count: props.setup_status.mappings_count,
        action: 'Configurar Mapeos',
    },
];

const completedSteps = computed(() => steps.filter(step => step.completed).length);
const totalSteps = steps.length;
const progressPercentage = computed(() => (completedSteps.value / totalSteps) * 100);
const isComplete = computed(() => completedSteps.value === totalSteps);

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Configuración Contable',
        href: '/setup/accounting-wizard',
    },
];

const quickSetup = () => {
    // Redirect to a page that automatically runs all seeders
    router.post('/setup/accounting-wizard/quick-setup');
};
</script>

<template>

    <Head title="Configuración Contable" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">Configuración del Sistema Contable</h1>
                <p class="mt-2 text-lg text-gray-600">
                    Complete estos pasos para configurar completamente el módulo contable de Tavira
                </p>
            </div>

            <!-- Progress Overview -->
            <Card class="mx-auto w-full max-w-2xl">
                <CardHeader class="text-center">
                    <CardTitle class="flex items-center justify-center gap-2">
                        <Zap class="h-6 w-6 text-blue-600" />
                        Progreso de Configuración
                    </CardTitle>
                    <CardDescription>{{ completedSteps }} de {{ totalSteps }} pasos completados</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Progress :value="progressPercentage" class="h-3" />
                    <div class="text-center text-sm text-gray-600">
                        {{ Math.round(progressPercentage) }}% completado
                    </div>

                    <!-- Quick Setup Button -->
                    <div v-if="!isComplete" class="text-center">
                        <Button @click="quickSetup" class="mt-4">
                            <Zap class="mr-2 h-4 w-4" />
                            Configuración Rápida
                        </Button>
                        <p class="mt-2 text-xs text-gray-500">
                            Configura automáticamente todos los elementos básicos
                        </p>
                    </div>

                    <!-- Success Message -->
                    <Alert v-if="isComplete" class="bg-green-50 border-green-200">
                        <CheckCircle class="h-4 w-4 text-green-600" />
                        <AlertDescription class="text-green-800">
                            ¡Configuración completada! Su sistema contable está listo para facturar.
                        </AlertDescription>
                    </Alert>
                </CardContent>
            </Card>

            <!-- Steps -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card v-for="(step, index) in steps" :key="step.id" class="relative">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-full',
                                    step.completed
                                        ? 'bg-green-100 text-green-600'
                                        : 'bg-gray-100 text-gray-600'
                                ]">
                                    <CheckCircle v-if="step.completed" class="h-6 w-6" />
                                    <component v-else :is="step.icon" class="h-6 w-6" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg">{{ step.title }}</CardTitle>
                                    <CardDescription class="text-sm">{{ step.description }}</CardDescription>
                                </div>
                            </div>
                            <div :class="[
                                'rounded-full px-2 py-1 text-xs font-medium',
                                step.completed
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-yellow-100 text-yellow-800'
                            ]">
                                {{ step.completed ? 'Completado' : 'Pendiente' }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-if="step.completed && step.count > 0" class="text-sm text-gray-600">
                                ✅ {{ step.count }} elementos configurados
                            </div>
                            <Button asChild :variant="step.completed ? 'outline' : 'default'" class="w-full">
                                <Link :href="step.route">
                                {{ step.completed ? 'Revisar Configuración' : step.action }}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Next Steps -->
            <Card v-if="isComplete" class="mx-auto w-full max-w-2xl bg-gradient-to-r from-blue-50 to-indigo-50">
                <CardHeader class="text-center">
                    <CardTitle class="text-blue-900">¡Listo para Facturar!</CardTitle>
                    <CardDescription class="text-blue-700">
                        Su sistema contable está completamente configurado
                    </CardDescription>
                </CardHeader>
                <CardContent class="text-center space-y-4">
                    <p class="text-blue-800">
                        Ahora puede crear facturas que generarán automáticamente las transacciones contables
                        correspondientes.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <Button asChild variant="default">
                            <Link href="/invoices/create">
                            <FileText class="mr-2 h-4 w-4" />
                            Crear Primera Factura
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/invoices">
                            Ver Facturas
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
