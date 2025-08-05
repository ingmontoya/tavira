<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, ToggleLeft, ToggleRight, Trash2 } from 'lucide-vue-next';

interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    type: string;
    subtype?: string;
}

interface ConjuntoConfig {
    id: number;
    name: string;
}

interface PaymentMethodAccountMapping {
    id: number;
    payment_method: string;
    payment_method_label: string;
    cash_account_id: number;
    cash_account: ChartOfAccount;
    is_active: boolean;
    conjunto_config: ConjuntoConfig;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    mapping: PaymentMethodAccountMapping;
}>();

const toggleMapping = () => {
    router.post(
        `/payment-method-account-mappings/${props.mapping.id}/toggle`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const deleteMapping = () => {
    if (confirm('¿Estás seguro de que deseas eliminar este mapeo de cuenta?')) {
        router.delete(`/payment-method-account-mappings/${props.mapping.id}`);
    }
};

const getPaymentMethodColor = (paymentMethod: string) => {
    const colors = {
        cash: 'bg-green-100 text-green-800',
        bank_transfer: 'bg-blue-100 text-blue-800',
        check: 'bg-purple-100 text-purple-800',
        credit_card: 'bg-orange-100 text-orange-800',
        debit_card: 'bg-yellow-100 text-yellow-800',
        online: 'bg-indigo-100 text-indigo-800',
        pse: 'bg-pink-100 text-pink-800',
        other: 'bg-gray-100 text-gray-800',
    };
    return colors[paymentMethod as keyof typeof colors] || 'bg-gray-100 text-gray-800';
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
        title: `Mapeo #${props.mapping.id}`,
        href: `/payment-method-account-mappings/${props.mapping.id}`,
    },
];
</script>

<template>
    <Head :title="`Mapeo de Cuenta #${mapping.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Mapeo de Cuenta #{{ mapping.id }}</h1>
                    <p class="text-muted-foreground">Detalle de la configuración del mapeo entre método de pago y cuenta contable</p>
                </div>
                <div class="flex space-x-2">
                    <Button @click="toggleMapping" :variant="mapping.is_active ? 'outline' : 'default'">
                        <ToggleLeft v-if="mapping.is_active" class="mr-2 h-4 w-4" />
                        <ToggleRight v-else class="mr-2 h-4 w-4" />
                        {{ mapping.is_active ? 'Desactivar' : 'Activar' }}
                    </Button>
                    <Button asChild>
                        <Link :href="`/payment-method-account-mappings/${mapping.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                    <Button variant="destructive" @click="deleteMapping">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/payment-method-account-mappings">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al Listado
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>Detalles del mapeo de cuenta</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-muted-foreground">Método de Pago</h4>
                                    <Badge :class="getPaymentMethodColor(mapping.payment_method)">
                                        {{ mapping.payment_method_label }}
                                    </Badge>
                                </div>
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-muted-foreground">Estado</h4>
                                    <Badge :class="mapping.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                        {{ mapping.is_active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Cuenta Contable Asociada</h4>
                                <div class="rounded-lg border p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="font-medium">{{ mapping.cash_account.code }} - {{ mapping.cash_account.name }}</h5>
                                            <p class="text-sm text-muted-foreground">
                                                {{ mapping.cash_account.type }}
                                                <span v-if="mapping.cash_account.subtype">/ {{ mapping.cash_account.subtype }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Timestamps -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de Sistema</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-muted-foreground">Fecha de Creación</h4>
                                    <p class="text-sm">{{ new Date(mapping.created_at).toLocaleString('es-CO') }}</p>
                                </div>
                                <div class="space-y-2">
                                    <h4 class="text-sm font-medium text-muted-foreground">Última Modificación</h4>
                                    <p class="text-sm">{{ new Date(mapping.updated_at).toLocaleString('es-CO') }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Acciones Rápidas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button asChild variant="outline" class="w-full justify-start">
                                <Link :href="`/payment-method-account-mappings/${mapping.id}/edit`">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Editar Mapeo
                                </Link>
                            </Button>
                            <Button @click="toggleMapping" variant="outline" class="w-full justify-start">
                                <ToggleLeft v-if="mapping.is_active" class="mr-2 h-4 w-4" />
                                <ToggleRight v-else class="mr-2 h-4 w-4" />
                                {{ mapping.is_active ? 'Desactivar' : 'Activar' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Información</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium">Conjunto</h4>
                                <p class="text-sm text-muted-foreground">{{ mapping.conjunto_config.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium">Propósito</h4>
                                <p class="text-sm text-muted-foreground">
                                    Este mapeo define que todos los pagos recibidos con el método "{{ mapping.payment_method_label }}" se registrarán
                                    en la cuenta contable "{{ mapping.cash_account.name }}".
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Warning -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg text-orange-600">Importante</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Al eliminar este mapeo, los pagos futuros con este método podrían usar el mapeo por defecto o generar errores en el
                                registro contable.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
