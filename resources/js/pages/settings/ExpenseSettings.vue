<template>
    <AppLayout>
        <Head title="Configuración de Gastos" />
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Configuración de Gastos</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Configurar aprobaciones, categorías automáticas y notificaciones para gastos del conjunto.
                    </p>
                </div>

                <form @submit.prevent="updateSettings" class="space-y-8">
                    <!-- Approval Configuration Section -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <ShieldCheckIcon class="h-5 w-5" />
                                <span>Configuración de Aprobaciones</span>
                            </CardTitle>
                            <CardDescription>
                                Configurar cuando los gastos requieren aprobación y notificaciones al consejo administrativo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Switch id="approval-required" v-model:checked="form.approval_required" />
                                <Label for="approval-required">Requerir aprobación para gastos</Label>
                            </div>

                            <div v-if="form.approval_required" class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="approval-threshold">Monto mínimo para aprobación</Label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                                {{ form.approval_threshold_currency === 'COP' ? '$' : '$' }}
                                            </span>
                                            <Input
                                                id="approval-threshold"
                                                type="number"
                                                step="1000"
                                                v-model.number="form.approval_threshold_amount"
                                                :min="0"
                                                :max="100000000"
                                                class="pl-8"
                                                required
                                            />
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            Gastos por encima de este monto requerirán aprobación previa
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="threshold-currency">Moneda</Label>
                                        <Select v-model="form.approval_threshold_currency">
                                            <SelectTrigger id="threshold-currency">
                                                <SelectValue placeholder="Seleccionar moneda" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="COP">
                                                    <span class="flex items-center gap-2">
                                                        <span>COP - Peso Colombiano</span>
                                                    </span>
                                                </SelectItem>
                                                <SelectItem value="USD">
                                                    <span class="flex items-center gap-2">
                                                        <span>USD - Dólar Americano</span>
                                                    </span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-gray-500">Moneda para el monto de aprobación</p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Switch id="council-approval" v-model:checked="form.council_approval_required" />
                                    <Label for="council-approval">Requerir aprobación del consejo administrativo</Label>
                                </div>

                                <div v-if="form.council_approval_required" class="space-y-2">
                                    <Label for="council-email">Email de notificación del consejo</Label>
                                    <Input
                                        id="council-email"
                                        type="email"
                                        v-model="form.council_approval_notification_email"
                                        placeholder="consejo@conjunto.com"
                                        required
                                    />
                                    <p class="text-xs text-gray-500">
                                        Email donde se enviarán las notificaciones de gastos pendientes de aprobación
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Auto-Approval Configuration Section -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <CheckCircleIcon class="h-5 w-5" />
                                <span>Configuración de Auto-Aprobación</span>
                            </CardTitle>
                            <CardDescription>
                                Configurar categorías y condiciones para la aprobación automática de gastos.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Switch id="auto-approve-threshold" v-model:checked="form.auto_approve_below_threshold" />
                                <Label for="auto-approve-threshold">Auto-aprobar gastos por debajo del monto mínimo</Label>
                            </div>
                            <p class="text-xs text-gray-500 ml-6">
                                Los gastos por debajo del monto configurado se aprobarán automáticamente
                            </p>

                            <div class="space-y-2">
                                <Label>Categorías con auto-aprobación</Label>
                                <div class="border rounded-lg p-3">
                                    <div class="space-y-2">
                                        <div 
                                            v-for="category in expenseCategories" 
                                            :key="category.id"
                                            class="flex items-center space-x-2"
                                        >
                                            <input
                                                type="checkbox"
                                                :id="`category-${category.id}`"
                                                :value="category.id"
                                                v-model="form.auto_approve_categories"
                                                class="rounded border-gray-300 text-blue-600 focus:border-blue-500 focus:ring-blue-500"
                                            />
                                            <Label
                                                :for="`category-${category.id}`"
                                                class="flex items-center space-x-2 cursor-pointer"
                                            >
                                                <div
                                                    class="w-3 h-3 rounded-full"
                                                    :style="{ backgroundColor: category.color }"
                                                ></div>
                                                <span class="text-sm">{{ category.name }}</span>
                                            </Label>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Los gastos en estas categorías se aprobarán automáticamente independiente del monto
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notification Settings Section -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <BellIcon class="h-5 w-5" />
                                <span>Configuración de Notificaciones</span>
                            </CardTitle>
                            <CardDescription>
                                Configurar cuándo enviar notificaciones por email sobre el estado de los gastos.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <Label for="notify-pending">Notificar gastos pendientes de aprobación</Label>
                                        <p class="text-xs text-gray-500">
                                            Enviar notificación cuando un gasto quede pendiente de aprobación
                                        </p>
                                    </div>
                                    <Switch id="notify-pending" v-model:checked="form.notify_on_pending_approval" />
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <Label for="notify-approved">Notificar gastos aprobados</Label>
                                        <p class="text-xs text-gray-500">
                                            Enviar notificación cuando un gasto sea aprobado
                                        </p>
                                    </div>
                                    <Switch id="notify-approved" v-model:checked="form.notify_on_approval_granted" />
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <Label for="notify-rejected">Notificar gastos rechazados</Label>
                                        <p class="text-xs text-gray-500">
                                            Enviar notificación cuando un gasto sea rechazado
                                        </p>
                                    </div>
                                    <Switch id="notify-rejected" v-model:checked="form.notify_on_approval_rejected" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Summary Card -->
                    <Card class="bg-blue-50 border-blue-200">
                        <CardContent class="pt-6">
                            <div class="flex items-start gap-4">
                                <InfoIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                                <div>
                                    <h4 class="font-medium text-blue-900 mb-2">Resumen de Configuración</h4>
                                    <div class="text-sm text-blue-800 space-y-1">
                                        <p v-if="!form.approval_required">
                                            • Todos los gastos se aprobarán automáticamente sin revisión
                                        </p>
                                        <p v-else>
                                            • Gastos por encima de {{ formatCurrency(form.approval_threshold_amount, form.approval_threshold_currency) }} requerirán aprobación
                                        </p>
                                        <p v-if="form.approval_required && form.auto_approve_below_threshold">
                                            • Gastos por debajo del monto se aprobarán automáticamente
                                        </p>
                                        <p v-if="form.auto_approve_categories.length > 0">
                                            • {{ form.auto_approve_categories.length }} categoría(s) configurada(s) para auto-aprobación
                                        </p>
                                        <p v-if="form.council_approval_required && form.council_approval_notification_email">
                                            • Se notificará al consejo en: {{ form.council_approval_notification_email }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            @click="resetForm"
                            :disabled="form.processing"
                        >
                            Restablecer
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <template v-if="form.processing">
                                Guardando...
                            </template>
                            <template v-else>
                                Guardar Configuración
                            </template>
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { BellIcon, CheckCircleIcon, InfoIcon, ShieldCheckIcon } from 'lucide-vue-next';

interface ExpenseSettings {
    approval_required: boolean;
    approval_threshold_amount: number;
    approval_threshold_currency: 'COP' | 'USD';
    council_approval_required: boolean;
    council_approval_notification_email: string;
    auto_approve_below_threshold: boolean;
    auto_approve_categories: number[];
    notify_on_pending_approval: boolean;
    notify_on_approval_granted: boolean;
    notify_on_approval_rejected: boolean;
}

interface ExpenseCategory {
    id: number;
    name: string;
    color: string;
    is_active: boolean;
}

interface Props {
    settings: ExpenseSettings;
    expenseCategories: ExpenseCategory[];
}

const props = defineProps<Props>();
const { success } = useToast();

// Initialize form with current settings
const form = useForm({
    approval_required: props.settings.approval_required,
    approval_threshold_amount: props.settings.approval_threshold_amount,
    approval_threshold_currency: props.settings.approval_threshold_currency,
    council_approval_required: props.settings.council_approval_required,
    council_approval_notification_email: props.settings.council_approval_notification_email,
    auto_approve_below_threshold: props.settings.auto_approve_below_threshold,
    auto_approve_categories: [...props.settings.auto_approve_categories],
    notify_on_pending_approval: props.settings.notify_on_pending_approval,
    notify_on_approval_granted: props.settings.notify_on_approval_granted,
    notify_on_approval_rejected: props.settings.notify_on_approval_rejected,
});

const updateSettings = () => {
    form.post(route('settings.expenses'), {
        preserveScroll: true,
        onSuccess: () => {
            success('Configuración de gastos actualizada correctamente');
        },
    });
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
};

const formatCurrency = (amount: number, currency: string): string => {
    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    
    return formatter.format(amount);
};
</script>