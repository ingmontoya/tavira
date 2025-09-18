<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Settings } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    type: string;
    subtype?: string;
}

defineProps<{
    cashAccounts: ChartOfAccount[];
    paymentMethods: Record<string, string>;
}>();

const form = useForm({
    payment_method: '',
    cash_account_id: '',
    is_active: true,
});

const { error, success } = useToast();

const submit = () => {
    form.post('/payment-method-account-mappings');
};

const createDefaultMappings = () => {
    router.post('/payment-method-account-mappings/create-defaults', {}, {
        onSuccess: () => {
            success('Mapeos por defecto creados exitosamente');
        },
        onError: (errors) => {
            const errorMessage = errors?.create_defaults || 'Error al crear mapeos por defecto';
            error(errorMessage);
        }
    });
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Mapeo de Cuentas de Pago',
        href: '/payment-method-account-mappings',
    },
    {
        title: 'Crear Mapeo',
        href: '/payment-method-account-mappings/create',
    },
];
</script>

<template>
    <Head title="Crear Mapeo de Cuenta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Crear Mapeo de Cuenta</h1>
                    <p class="text-muted-foreground">Configura qué cuenta contable se usa para un método de pago específico</p>
                </div>
                <Button asChild variant="outline">
                    <Link href="/payment-method-account-mappings">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver al Listado
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-3">
                <!-- Main Form -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Mapeo</CardTitle>
                            <CardDescription>Define cómo se asocian los métodos de pago con las cuentas contables</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Payment Method -->
                                <div class="space-y-2">
                                    <Label for="payment_method">Método de Pago *</Label>
                                    <Select v-model="form.payment_method" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar método de pago" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="(label, key) in paymentMethods" :key="key" :value="key">
                                                {{ label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.payment_method" class="text-sm text-destructive">
                                        {{ form.errors.payment_method }}
                                    </div>
                                </div>

                                <!-- Cash Account -->
                                <div class="space-y-2">
                                    <Label for="cash_account_id">Cuenta Contable *</Label>
                                    <Select v-model="form.cash_account_id" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar cuenta contable" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="account in cashAccounts" :key="account.id" :value="account.id.toString()">
                                                {{ account.code }} - {{ account.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.cash_account_id" class="text-sm text-destructive">
                                        {{ form.errors.cash_account_id }}
                                    </div>
                                    <p class="text-sm text-muted-foreground">Solo se muestran cuentas de activos de tipo efectivo o bancarias</p>
                                </div>

                                <!-- Active Status -->
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_active" v-model:checked="form.is_active" />
                                    <Label for="is_active">Mapeo activo</Label>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <Button type="submit" :disabled="form.processing">
                                        <Save class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Creando...' : 'Crear Mapeo' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Help Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">¿Qué es un Mapeo de Cuenta?</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">
                                Los mapeos de cuentas definen qué cuenta contable se utiliza cuando se registra un pago con un método específico.
                            </p>
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium">Ejemplos:</h4>
                                <ul class="space-y-1 text-sm text-muted-foreground">
                                    <li>• Efectivo → Caja General</li>
                                    <li>• Transferencia → Banco Davivienda</li>
                                    <li>• Tarjeta → Banco Bogotá</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Available Accounts Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Cuentas Disponibles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">{{ cashAccounts.length }} cuentas de efectivo/banco disponibles</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Setup Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Configuración Rápida</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <p class="text-sm text-muted-foreground">
                                    ¿Prefieres crear todos los mapeos básicos de una vez? Esto configurará automáticamente los métodos de pago más comunes.
                                </p>
                                <Button @click="createDefaultMappings" variant="default" size="sm" class="w-full">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Crear Mapeos por Defecto
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
