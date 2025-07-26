<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
// Removed reactive refs - using direct form binding

interface ApartmentType {
    id: number;
    name: string;
}

interface PaymentConcept {
    id: number;
    name: string;
    description?: string;
    type: string;
    default_amount: number;
    is_recurring: boolean;
    is_active: boolean;
    billing_cycle: string;
    applicable_apartment_types?: number[];
}

const props = defineProps<{
    concept: PaymentConcept;
    apartmentTypes: ApartmentType[];
}>();

const form = useForm({
    name: props.concept.name,
    description: props.concept.description || '',
    type: props.concept.type,
    default_amount: props.concept.default_amount,
    is_recurring: props.concept.is_recurring,
    is_active: props.concept.is_active,
    billing_cycle: props.concept.billing_cycle,
    applicable_apartment_types: props.concept.applicable_apartment_types || [],
});

const submit = () => {
    form.put(`/payment-concepts/${props.concept.id}`, {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const toggleApartmentType = (typeId: number, checked: boolean) => {
    if (checked) {
        if (!form.applicable_apartment_types.includes(typeId)) {
            form.applicable_apartment_types.push(typeId);
        }
    } else {
        const index = form.applicable_apartment_types.indexOf(typeId);
        if (index > -1) {
            form.applicable_apartment_types.splice(index, 1);
        }
    }
};

const typeDescriptions = {
    common_expense: 'Gastos comunes del conjunto (administración, vigilancia, etc.)',
    sanction: 'Multas por incumplimiento de normas',
    parking: 'Cuotas de parqueadero adicional',
    special: 'Conceptos especiales o extraordinarios',
    late_fee: 'Intereses por mora en pagos',
    other: 'Otros conceptos de facturación',
};

const billingCycleDescriptions = {
    monthly: 'Se factura cada mes',
    quarterly: 'Se factura cada tres meses',
    annually: 'Se factura una vez al año',
    one_time: 'Se factura una sola vez',
};

const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Gestión de Pagos', href: '/payments' },
    { title: 'Conceptos de Pago', href: '/payment-concepts' },
    { title: 'Editar Concepto' },
];
</script>

<template>
    <Head :title="`Editar: ${concept.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar: {{ concept.name }}</h1>
                    <p class="text-muted-foreground">Modifica la configuración de este concepto de pago</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button asChild variant="outline" size="sm">
                        <Link :href="`/payment-concepts/${concept.id}`">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al Concepto
                        </Link>
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Form Fields -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Basic Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Básica</CardTitle>
                                <CardDescription> Información general del concepto de pago </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Name -->
                                <div class="space-y-2">
                                    <Label for="name">Nombre del Concepto *</Label>
                                    <Input id="name" v-model="form.name" placeholder="Ej: Administración, Vigilancia, Sanción por ruido" required />
                                    <div v-if="form.errors.name" class="text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <Label for="description">Descripción (Opcional)</Label>
                                    <Textarea id="description" v-model="form.description" placeholder="Descripción detallada del concepto de pago" />
                                    <div v-if="form.errors.description" class="text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <!-- Type -->
                                <div class="space-y-2">
                                    <Label for="type">Tipo de Concepto *</Label>
                                    <Select v-model="form.type" required>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="common_expense">Administración</SelectItem>
                                            <SelectItem value="sanction">Sanción</SelectItem>
                                            <SelectItem value="parking">Parqueadero</SelectItem>
                                            <SelectItem value="special">Especial</SelectItem>
                                            <SelectItem value="late_fee">Interés de mora</SelectItem>
                                            <SelectItem value="other">Otro</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-muted-foreground">
                                        {{ typeDescriptions[form.type as keyof typeof typeDescriptions] }}
                                    </p>
                                    <div v-if="form.errors.type" class="text-sm text-red-600">
                                        {{ form.errors.type }}
                                    </div>
                                </div>

                                <!-- Default Amount -->
                                <div class="space-y-2">
                                    <Label for="default_amount">Monto por Defecto *</Label>
                                    <Input id="default_amount" type="number" min="0" step="0.01" v-model.number="form.default_amount" required />
                                    <p class="text-sm text-muted-foreground">Monto que se usará por defecto al facturar este concepto</p>
                                    <div v-if="form.errors.default_amount" class="text-sm text-red-600">
                                        {{ form.errors.default_amount }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Configuration -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración de Facturación</CardTitle>
                                <CardDescription> Configuración sobre cómo y cuándo se factura este concepto </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Billing Cycle -->
                                <div class="space-y-2">
                                    <Label for="billing_cycle">Ciclo de Facturación *</Label>
                                    <Select v-model="form.billing_cycle" required>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="monthly">Mensual</SelectItem>
                                            <SelectItem value="quarterly">Trimestral</SelectItem>
                                            <SelectItem value="annually">Anual</SelectItem>
                                            <SelectItem value="one_time">Una vez</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-muted-foreground">
                                        {{ billingCycleDescriptions[form.billing_cycle as keyof typeof billingCycleDescriptions] }}
                                    </p>
                                    <div v-if="form.errors.billing_cycle" class="text-sm text-red-600">
                                        {{ form.errors.billing_cycle }}
                                    </div>
                                </div>

                                <!-- Boolean Controls -->
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="is_recurring" v-model="form.is_recurring" />
                                        <div class="grid gap-1.5 leading-none">
                                            <Label
                                                for="is_recurring"
                                                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                            >
                                                Es recurrente
                                            </Label>
                                            <p class="text-xs text-muted-foreground">Se incluirá automáticamente en la facturación masiva mensual</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="is_active" v-model="form.is_active" />
                                        <div class="grid gap-1.5 leading-none">
                                            <Label
                                                for="is_active"
                                                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                            >
                                                Activo
                                            </Label>
                                            <p class="text-xs text-muted-foreground">Estará disponible para facturar</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Apartment Types -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Tipos de Apartamento Aplicables</CardTitle>
                                <CardDescription>
                                    Selecciona a qué tipos de apartamento aplica este concepto. Si no seleccionas ninguno, aplicará a todos.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div v-for="apartmentType in apartmentTypes" :key="apartmentType.id" class="flex items-center space-x-2">
                                    <Checkbox
                                        :id="`apartment-type-${apartmentType.id}`"
                                        :checked="form.applicable_apartment_types.includes(apartmentType.id)"
                                        @update:checked="(checked) => toggleApartmentType(apartmentType.id, checked)"
                                    />
                                    <Label
                                        :for="`apartment-type-${apartmentType.id}`"
                                        class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        {{ apartmentType.name }}
                                    </Label>
                                </div>

                                <div v-if="form.applicable_apartment_types.length === 0" class="text-sm text-muted-foreground">
                                    Este concepto aplicará a todos los tipos de apartamento
                                </div>

                                <div v-if="form.errors.applicable_apartment_types" class="text-sm text-red-600">
                                    {{ form.errors.applicable_apartment_types }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Summary -->
                    <div class="space-y-6">
                        <!-- Preview -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Vista Previa</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="form.name">
                                    <Label class="text-sm font-medium text-muted-foreground">Nombre</Label>
                                    <p class="font-medium">{{ form.name }}</p>
                                </div>

                                <div v-if="form.description">
                                    <Label class="text-sm font-medium text-muted-foreground">Descripción</Label>
                                    <p class="text-sm">{{ form.description }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Monto por Defecto</Label>
                                    <p class="text-lg font-medium">{{ formatCurrency(form.default_amount) }}</p>
                                </div>

                                <div>
                                    <Label class="text-sm font-medium text-muted-foreground">Características</Label>
                                    <div class="space-y-1 text-sm">
                                        <p>• Tipo: {{ typeDescriptions[form.type as keyof typeof typeDescriptions] }}</p>
                                        <p>• Ciclo: {{ billingCycleDescriptions[form.billing_cycle as keyof typeof billingCycleDescriptions] }}</p>
                                        <p>• Recurrente: {{ form.is_recurring ? 'Sí' : 'No' }}</p>
                                        <p>• Estado: {{ form.is_active ? 'Activo' : 'Inactivo' }}</p>
                                    </div>
                                </div>

                                <div v-if="form.applicable_apartment_types.length > 0">
                                    <Label class="text-sm font-medium text-muted-foreground">Aplica a</Label>
                                    <div class="text-sm">
                                        {{
                                            apartmentTypes
                                                .filter((type) => form.applicable_apartment_types.includes(type.id))
                                                .map((type) => type.name)
                                                .join(', ')
                                        }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Submit -->
                        <Card>
                            <CardContent class="pt-6">
                                <Button type="submit" class="w-full" :disabled="form.processing || !form.name || form.default_amount < 0">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
