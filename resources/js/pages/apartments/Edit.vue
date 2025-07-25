<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building, CheckCircle, DollarSign, FileText, Home, Save, Settings, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface FormData {
    conjunto_config_id: number;
    apartment_type_id: number;
    number: string;
    tower: string;
    floor: number;
    position_on_floor: number;
    status: string;
    monthly_fee: number;
    utilities: Record<string, boolean>;
    features: Record<string, boolean>;
    notes: string;
}

const props = defineProps({
    apartment: Object,
    conjunto: Object,
    apartmentTypes: Array,
    statuses: Array,
});

// Form state
const form = useForm<FormData>({
    conjunto_config_id: props.apartment.conjunto_config_id,
    apartment_type_id: props.apartment.apartment_type_id,
    number: props.apartment.number,
    tower: props.apartment.tower,
    floor: props.apartment.floor,
    position_on_floor: props.apartment.position_on_floor,
    status: props.apartment.status,
    monthly_fee: props.apartment.monthly_fee,
    utilities: props.apartment.utilities || {},
    features: props.apartment.features || {},
    notes: props.apartment.notes || '',
});

// UI state
const currentStep = ref(0);
const isUnsavedChanges = ref(false);

// Form validation
const validationRules = {
    step1: ['conjunto_config_id', 'apartment_type_id', 'number', 'tower', 'floor', 'position_on_floor'],
    step2: ['status', 'monthly_fee'],
    step3: [],
    step4: [],
};

const steps = [
    {
        id: 0,
        title: 'Información Básica',
        description: 'Ubicación y tipo de apartamento',
        icon: Home,
        fields: ['conjunto_config_id', 'apartment_type_id', 'number', 'tower', 'floor', 'position_on_floor'],
    },
    {
        id: 1,
        title: 'Estado y Cuota',
        description: 'Estado del apartamento y cuota mensual',
        icon: DollarSign,
        fields: ['status', 'monthly_fee'],
    },
    {
        id: 2,
        title: 'Características',
        description: 'Servicios y características adicionales',
        icon: Settings,
        fields: ['utilities', 'features'],
    },
    {
        id: 3,
        title: 'Notas',
        description: 'Información adicional',
        icon: FileText,
        fields: ['notes'],
    },
];

// Computed properties
const currentStepData = computed(() => steps[currentStep.value]);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const isFirstStep = computed(() => currentStep.value === 0);

const completedSteps = computed(() => {
    return steps.filter((step, index) => {
        if (index >= currentStep.value) return false;
        return validateStep(index);
    }).length;
});

const progressPercentage = computed(() => {
    return ((currentStep.value + 1) / steps.length) * 100;
});

const formProgress = computed(() => {
    const totalFields = steps.reduce((acc, step) => acc + step.fields.length, 0);
    const completedFields = steps.reduce((acc, step) => {
        return (
            acc +
            step.fields.filter((field) => {
                const value = form[field];
                if (typeof value === 'boolean') return true;
                if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0;
                return value && value.toString().trim() !== '';
            }).length
        );
    }, 0);

    return Math.round((completedFields / totalFields) * 100);
});

const canProceedToNext = computed(() => {
    return validateStep(currentStep.value);
});

// Utilities and features state
const utilities = ref({
    water: props.apartment.utilities?.water || false,
    electricity: props.apartment.utilities?.electricity || false,
    gas: props.apartment.utilities?.gas || false,
    internet: props.apartment.utilities?.internet || false,
    tv: props.apartment.utilities?.tv || false,
    phone: props.apartment.utilities?.phone || false,
});

const features = ref({
    parking: props.apartment.features?.parking || false,
    storage: props.apartment.features?.storage || false,
    balcony: props.apartment.features?.balcony || false,
    terrace: props.apartment.features?.terrace || false,
    pets_allowed: props.apartment.features?.pets_allowed || false,
    furnished: props.apartment.features?.furnished || false,
});

// Watch for changes in utilities and features and update form
watch(
    utilities,
    (newUtilities) => {
        form.utilities = { ...newUtilities };
    },
    { deep: true },
);

watch(
    features,
    (newFeatures) => {
        form.features = { ...newFeatures };
    },
    { deep: true },
);

// Methods
const validateStep = (stepIndex: number): boolean => {
    const step = steps[stepIndex];
    const requiredFields = validationRules[`step${stepIndex + 1}` as keyof typeof validationRules] || [];

    return requiredFields.every((field) => {
        const value = form[field];
        if (typeof value === 'boolean') return true;
        if (typeof value === 'object' && value !== null) return Object.keys(value).length >= 0;
        return value && value.toString().trim() !== '';
    });
};

const nextStep = () => {
    if (canProceedToNext.value && !isLastStep.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (!isFirstStep.value) {
        currentStep.value--;
    }
};

const goToStep = (stepIndex: number) => {
    if (stepIndex <= currentStep.value || validateStep(stepIndex - 1)) {
        currentStep.value = stepIndex;
    }
};

const submit = () => {
    if (validateStep(currentStep.value)) {
        form.put(route('apartments.update', props.apartment.id), {
            onSuccess: () => {
                isUnsavedChanges.value = false;
            },
        });
    }
};

const resetForm = () => {
    form.reset();
    currentStep.value = 0;
    isUnsavedChanges.value = false;
};

// Watch for form changes
watch(
    form,
    () => {
        isUnsavedChanges.value = true;
    },
    { deep: true },
);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Apartamentos', href: '/apartments' },
    { title: props.apartment.full_address, href: `/apartments/${props.apartment.id}` },
    { title: 'Editar', href: `/apartments/${props.apartment.id}/edit` },
];

const getStatusLabel = (status: string) => {
    const labels = {
        Available: 'Disponible',
        Occupied: 'Ocupado',
        Maintenance: 'Mantenimiento',
        Reserved: 'Reservado',
    };
    return labels[status] || status;
};
</script>

<template>
    <Head :title="`Editar ${apartment.full_address}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Apartamento</h1>
                    <p class="text-muted-foreground">
                        {{ apartment.full_address }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="`/apartments/${apartment.id}`">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <AlertDialog v-if="isUnsavedChanges">
                        <AlertDialogTrigger as-child>
                            <Button variant="outline" class="gap-2">
                                <X class="h-4 w-4" />
                                Cancelar
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>¿Descartar cambios?</AlertDialogTitle>
                                <AlertDialogDescription> Tienes cambios sin guardar. ¿Estás seguro de que deseas salir? </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Continuar editando</AlertDialogCancel>
                                <AlertDialogAction @click="resetForm"> Descartar cambios </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Progress Overview -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">Progreso del Formulario</CardTitle>
                            <CardDescription> Completa todos los campos para actualizar el apartamento </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">{{ formProgress }}% completado</span>
                            <Progress :value="formProgress" class="w-32" />
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Step Navigation -->
            <div class="mb-8 grid grid-cols-4 gap-4">
                <div
                    v-for="(step, index) in steps"
                    :key="step.id"
                    @click="goToStep(index)"
                    :class="[
                        'relative cursor-pointer rounded-lg border p-4 transition-all duration-200',
                        {
                            'border-primary bg-primary text-primary-foreground': currentStep === index,
                            'border-green-200 bg-green-50 text-green-800': index < currentStep && validateStep(index),
                            'border-muted bg-muted/50 text-muted-foreground': index > currentStep,
                            'hover:bg-muted/80': index <= currentStep,
                        },
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-8 w-8 items-center justify-center rounded-full',
                                {
                                    'bg-primary-foreground text-primary': currentStep === index,
                                    'bg-green-500 text-white': index < currentStep && validateStep(index),
                                    'bg-muted text-muted-foreground': index > currentStep,
                                },
                            ]"
                        >
                            <CheckCircle v-if="index < currentStep && validateStep(index)" class="h-4 w-4" />
                            <component v-else :is="step.icon" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ step.title }}</p>
                            <p class="truncate text-xs opacity-75">{{ step.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- Sidebar with summary -->
                <div class="lg:col-span-1">
                    <Card class="sticky top-4">
                        <CardHeader>
                            <CardTitle class="text-lg">Resumen</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Home class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ form.number || 'Número' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">Torre {{ form.tower || 'N/A' }} - Piso {{ form.floor || 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Building class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">
                                        {{ conjunto.name || 'Conjunto no seleccionado' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <DollarSign class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">${{ form.monthly_fee?.toLocaleString() || '0' }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Settings class="h-4 w-4 text-muted-foreground" />
                                    <Badge :variant="form.status ? 'default' : 'secondary'">
                                        {{ getStatusLabel(form.status) || 'Estado' }}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <p class="text-sm font-medium">Estado del formulario</p>
                                <div class="space-y-1">
                                    <div v-for="(step, index) in steps" :key="step.id" class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                {
                                                    'bg-green-500': index < currentStep && validateStep(index),
                                                    'bg-primary': currentStep === index,
                                                    'bg-muted': index > currentStep,
                                                },
                                            ]"
                                        />
                                        <span class="text-xs">{{ step.title }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Main form -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Step 1: Basic Information -->
                        <Card v-if="currentStep === 0">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Home class="h-5 w-5" />
                                    Información Básica
                                </CardTitle>
                                <CardDescription> Información de ubicación y tipo del apartamento </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Conjunto Config -->
                                    <div class="space-y-2">
                                        <Label for="conjunto_config_id">{{ conjunto.name }}</Label>
                                    </div>

                                    <!-- Apartment Type -->
                                    <div class="space-y-2">
                                        <Label for="apartment_type_id">Tipo de Apartamento *</Label>
                                        <Select v-model="form.apartment_type_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="type in apartmentTypes" :key="type.id" :value="type.id">
                                                    {{ type.name }} ({{ type.area_sqm }}m²)
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.apartment_type_id" class="text-sm text-red-600">
                                            {{ form.errors.apartment_type_id }}
                                        </p>
                                    </div>

                                    <!-- Number -->
                                    <div class="space-y-2">
                                        <Label for="number">Número *</Label>
                                        <Input
                                            id="number"
                                            v-model="form.number"
                                            placeholder="Ej: 1101"
                                            :class="{ 'border-red-500': form.errors.number }"
                                        />
                                        <p v-if="form.errors.number" class="text-sm text-red-600">
                                            {{ form.errors.number }}
                                        </p>
                                    </div>

                                    <!-- Tower -->
                                    <div class="space-y-2">
                                        <Label for="tower">Torre *</Label>
                                        <Input
                                            id="tower"
                                            v-model="form.tower"
                                            placeholder="Ej: 1 o A"
                                            :class="{ 'border-red-500': form.errors.tower }"
                                        />
                                        <p v-if="form.errors.tower" class="text-sm text-red-600">
                                            {{ form.errors.tower }}
                                        </p>
                                    </div>

                                    <!-- Floor -->
                                    <div class="space-y-2">
                                        <Label for="floor">Piso *</Label>
                                        <Input
                                            id="floor"
                                            v-model="form.floor"
                                            type="number"
                                            min="1"
                                            placeholder="Ej: 11"
                                            :class="{ 'border-red-500': form.errors.floor }"
                                        />
                                        <p v-if="form.errors.floor" class="text-sm text-red-600">
                                            {{ form.errors.floor }}
                                        </p>
                                    </div>

                                    <!-- Position on Floor -->
                                    <div class="space-y-2">
                                        <Label for="position_on_floor">Posición en el piso *</Label>
                                        <Input
                                            id="position_on_floor"
                                            v-model="form.position_on_floor"
                                            type="number"
                                            min="1"
                                            placeholder="Ej: 1"
                                            :class="{ 'border-red-500': form.errors.position_on_floor }"
                                        />
                                        <p v-if="form.errors.position_on_floor" class="text-sm text-red-600">
                                            {{ form.errors.position_on_floor }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 2: Status and Fee -->
                        <Card v-if="currentStep === 1">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <DollarSign class="h-5 w-5" />
                                    Estado y Cuota
                                </CardTitle>
                                <CardDescription> Estado del apartamento y cuota mensual </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Status -->
                                    <div class="space-y-2">
                                        <Label for="status">Estado *</Label>
                                        <Select v-model="form.status">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un estado" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="status in statuses" :key="status" :value="status">
                                                    {{ getStatusLabel(status) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.status" class="text-sm text-red-600">
                                            {{ form.errors.status }}
                                        </p>
                                    </div>

                                    <!-- Monthly Fee -->
                                    <div class="space-y-2">
                                        <Label for="monthly_fee">Cuota Mensual *</Label>
                                        <Input
                                            id="monthly_fee"
                                            v-model="form.monthly_fee"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="Ej: 150000"
                                            :class="{ 'border-red-500': form.errors.monthly_fee }"
                                        />
                                        <p v-if="form.errors.monthly_fee" class="text-sm text-red-600">
                                            {{ form.errors.monthly_fee }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 3: Features -->
                        <Card v-if="currentStep === 2">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Características
                                </CardTitle>
                                <CardDescription> Servicios públicos y características adicionales </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Utilities -->
                                <div>
                                    <Label class="text-base font-medium">Servicios Públicos</Label>
                                    <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-3">
                                        <div class="flex items-center space-x-2">
                                            <Switch id="water" v-model:checked="utilities.water" />
                                            <Label for="water" class="text-sm">Agua</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="electricity" v-model:checked="utilities.electricity" />
                                            <Label for="electricity" class="text-sm">Electricidad</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="gas" v-model:checked="utilities.gas" />
                                            <Label for="gas" class="text-sm">Gas</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="internet" v-model:checked="utilities.internet" />
                                            <Label for="internet" class="text-sm">Internet</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="tv" v-model:checked="utilities.tv" />
                                            <Label for="tv" class="text-sm">TV</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="phone" v-model:checked="utilities.phone" />
                                            <Label for="phone" class="text-sm">Teléfono</Label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Features -->
                                <div>
                                    <Label class="text-base font-medium">Características</Label>
                                    <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-3">
                                        <div class="flex items-center space-x-2">
                                            <Switch id="parking" v-model:checked="features.parking" />
                                            <Label for="parking" class="text-sm">Parqueadero</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="storage" v-model:checked="features.storage" />
                                            <Label for="storage" class="text-sm">Depósito</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="balcony" v-model:checked="features.balcony" />
                                            <Label for="balcony" class="text-sm">Balcón</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="terrace" v-model:checked="features.terrace" />
                                            <Label for="terrace" class="text-sm">Terraza</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="pets_allowed" v-model:checked="features.pets_allowed" />
                                            <Label for="pets_allowed" class="text-sm">Mascotas permitidas</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Switch id="furnished" v-model:checked="features.furnished" />
                                            <Label for="furnished" class="text-sm">Amoblado</Label>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 4: Notes -->
                        <Card v-if="currentStep === 3">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Notas
                                </CardTitle>
                                <CardDescription> Información adicional sobre el apartamento </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="space-y-2">
                                    <Label for="notes">Notas Adicionales</Label>
                                    <Textarea
                                        id="notes"
                                        v-model="form.notes"
                                        placeholder="Información adicional sobre el apartamento..."
                                        rows="4"
                                        :class="{ 'border-red-500': form.errors.notes }"
                                    />
                                    <p v-if="form.errors.notes" class="text-sm text-red-600">
                                        {{ form.errors.notes }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Navigation buttons -->
                        <div class="flex items-center justify-between pt-6">
                            <Button type="button" variant="outline" :disabled="isFirstStep" @click="prevStep" class="gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Anterior
                            </Button>

                            <div class="flex items-center gap-3">
                                <Button v-if="!isLastStep" type="button" :disabled="!canProceedToNext" @click="nextStep" class="gap-2">
                                    Siguiente
                                    <ArrowLeft class="h-4 w-4 rotate-180" />
                                </Button>

                                <Button v-if="isLastStep" type="submit" :disabled="form.processing || !canProceedToNext" class="gap-2">
                                    <Save class="h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Actualizar Apartamento' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
