<template>
    <AppLayout>
        <Head title="Configuración Contable" />
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Configuración Contable</h1>
                    <p class="mt-1 text-sm text-gray-600">Configurar el sistema contable y plan de cuentas.</p>
                </div>

                <!-- Plan de Cuentas Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <BookOpenIcon class="h-5 w-5" />
                            <span>Plan de Cuentas</span>
                        </CardTitle>
                        <CardDescription>Configurar el plan de cuentas contable según la normativa colombiana (Decreto 2650).</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="!props.hasChartOfAccounts" class="space-y-4">
                            <Alert>
                                <AlertTriangleIcon class="h-4 w-4" />
                                <div class="ml-7">
                                    <h4 class="font-medium">Plan de cuentas no configurado</h4>
                                    <AlertDescription>
                                        El sistema contable requiere un plan de cuentas inicial. Haga clic en el botón siguiente para crear el plan de
                                        cuentas estándar para propiedad horizontal.
                                    </AlertDescription>
                                </div>
                            </Alert>

                            <div class="flex flex-col space-y-2">
                                <Button @click="initializeChartOfAccounts" :disabled="initializingAccounts" class="w-fit">
                                    <template v-if="initializingAccounts">
                                        <Loader2Icon class="mr-2 h-4 w-4 animate-spin" />
                                        Creando plan de cuentas...
                                    </template>
                                    <template v-else>
                                        <PlusIcon class="mr-2 h-4 w-4" />
                                        Crear Plan de Cuentas Inicial
                                    </template>
                                </Button>
                                <p class="text-xs text-gray-500">
                                    Se creará un plan de cuentas completo con 60+ cuentas siguiendo el Decreto 2650 y adaptado para conjuntos
                                    residenciales.
                                </p>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <Alert variant="success">
                                <CheckCircleIcon class="h-4 w-4" />
                                <div class="ml-7">
                                    <h4 class="font-medium">Plan de cuentas configurado</h4>
                                    <AlertDescription>
                                        El sistema contable está configurado con {{ props.accountsCount }} cuentas. El sistema de contabilidad está
                                        listo para operar.
                                    </AlertDescription>
                                </div>
                            </Alert>

                            <div class="flex space-x-3">
                                <Button @click="$inertia.visit(route('accounting.chart-of-accounts.index'))" variant="outline">
                                    <EyeIcon class="mr-2 h-4 w-4" />
                                    Ver Plan de Cuentas
                                </Button>
                                <Button @click="$inertia.visit(route('accounting.chart-of-accounts.create'))" variant="outline">
                                    <PlusIcon class="mr-2 h-4 w-4" />
                                    Agregar Cuenta
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuración Adicional -->
                <Card v-if="props.hasChartOfAccounts">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <SettingsIcon class="h-5 w-5" />
                            <span>Configuración Adicional</span>
                        </CardTitle>
                        <CardDescription>Configuraciones adicionales del sistema contable.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Button
                                @click="$inertia.visit(route('accounting.accounting-transactions.index'))"
                                variant="outline"
                                class="justify-start"
                            >
                                <FileTextIcon class="mr-2 h-4 w-4" />
                                Ver Transacciones Contables
                            </Button>
                            <Button @click="$inertia.visit(route('accounting.reports.index'))" variant="outline" class="justify-start">
                                <BarChart3Icon class="mr-2 h-4 w-4" />
                                Reportes Contables
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangleIcon,
    BarChart3Icon,
    BookOpenIcon,
    CheckCircleIcon,
    EyeIcon,
    FileTextIcon,
    Loader2Icon,
    PlusIcon,
    SettingsIcon,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    hasChartOfAccounts: boolean;
    accountsCount: number;
}

const props = defineProps<Props>();
const { success, error } = useToast();

const initializingAccounts = ref(false);

const initializeChartOfAccounts = async () => {
    if (initializingAccounts.value) return;

    initializingAccounts.value = true;

    try {
        await fetch(route('settings.accounting.initialize-accounts'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });

        success('Plan de cuentas creado exitosamente');

        // Recargar la página para mostrar el estado actualizado
        window.location.reload();
    } catch (err) {
        error('Error al crear el plan de cuentas');
        console.error(err);
    } finally {
        initializingAccounts.value = false;
    }
};
</script>
