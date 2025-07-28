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
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building, Calculator, CheckCircle, Home, Info, Plus, Save, Settings, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ApartmentType {
    name: string;
    description: string;
    area_sqm: number;
    bedrooms: number;
    bathrooms: number;
    has_balcony: boolean;
    has_laundry_room: boolean;
    has_maid_room: boolean;
    coefficient: number;
    administration_fee: number;
    floor_positions: number[];
}

interface FormData {
    name: string;
    description: string;
    number_of_towers: number;
    floors_per_tower: number;
    apartments_per_floor: number;
    tower_names: string[];
    apartment_types: ApartmentType[];
}

// Form state
const form = useForm<FormData>({
    name: '',
    description: '',
    number_of_towers: 1,
    floors_per_tower: 1,
    apartments_per_floor: 1,
    tower_names: ['A'],
    apartment_types: [],
});

// UI state
const currentStep = ref(0);
const isUnsavedChanges = ref(false);

const steps = [
    {
        id: 0,
        title: 'Información Básica',
        description: 'Datos generales del conjunto',
        icon: Building,
    },
    {
        id: 1,
        title: 'Configuración de Torres',
        description: 'Estructura física del conjunto',
        icon: Home,
    },
    {
        id: 2,
        title: 'Tipos de Apartamento',
        description: 'Definición de tipos y características',
        icon: Settings,
    },
];

// Computed properties
const _currentStepData = computed(() => steps[currentStep.value]);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const isFirstStep = computed(() => currentStep.value === 0);

const totalApartments = computed(() => {
    return form.number_of_towers * form.floors_per_tower * form.apartments_per_floor;
});

const formProgress = computed(() => {
    let progress = 0;

    // Step 1: Basic info (40%)
    if (form.name && form.number_of_towers && form.floors_per_tower && form.apartments_per_floor) {
        progress += 40;
    }

    // Step 2: Tower configuration (30%)
    if (form.tower_names.length === form.number_of_towers) {
        progress += 30;
    }

    // Step 3: Apartment types (30%)
    if (form.apartment_types.length > 0) {
        progress += 30;
    }

    return Math.min(progress, 100);
});

const canProceedToNext = computed(() => {
    switch (currentStep.value) {
        case 0:
            return form.name && form.number_of_towers > 0 && form.floors_per_tower > 0 && form.apartments_per_floor > 0;
        case 1:
            return form.tower_names.length === form.number_of_towers;
        case 2:
            return form.apartment_types.length > 0;
        default:
            return true;
    }
});

// Methods
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
    currentStep.value = stepIndex;
};

// Watch for tower count changes
watch(
    () => form.number_of_towers,
    (newCount, oldCount) => {
        if (newCount !== oldCount) {
            updateTowerNames();
            isUnsavedChanges.value = true;
        }
    },
);

const updateTowerNames = () => {
    const count = form.number_of_towers;
    const newNames = [];

    for (let i = 0; i < count; i++) {
        if (form.tower_names[i]) {
            newNames.push(form.tower_names[i]);
        } else {
            newNames.push(String.fromCharCode(65 + i)); // A, B, C, etc.
        }
    }

    form.tower_names = newNames;
};

const addApartmentType = () => {
    const newType: ApartmentType = {
        name: `Tipo ${String.fromCharCode(65 + form.apartment_types.length)}`,
        description: '',
        area_sqm: 70,
        bedrooms: 2,
        bathrooms: 2,
        has_balcony: true,
        has_laundry_room: true,
        has_maid_room: false,
        coefficient: 0.01,
        administration_fee: 450000,
        floor_positions: [1],
    };

    form.apartment_types.push(newType);
    isUnsavedChanges.value = true;
};

const removeApartmentType = (index: number) => {
    form.apartment_types.splice(index, 1);
    isUnsavedChanges.value = true;
};

const updateFloorPositions = (typeIndex: number, position: number, checked: boolean) => {
    const type = form.apartment_types[typeIndex];
    if (checked) {
        if (!type.floor_positions.includes(position)) {
            type.floor_positions.push(position);
        }
    } else {
        type.floor_positions = type.floor_positions.filter((p) => p !== position);
    }
    isUnsavedChanges.value = true;
};

const submit = () => {
    if (canProceedToNext.value) {
        form.post(route('conjunto-config.store'), {
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
    { title: 'Configuración de Conjuntos', href: '/conjunto-config' },
    { title: 'Nuevo Conjunto', href: '/conjunto-config/create' },
];
</script>

<template>
    <Head title="Nuevo Conjunto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nuevo Conjunto Residencial</h1>
                    <p class="text-muted-foreground">Configura un nuevo conjunto y genera apartamentos automáticamente</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/conjunto-config">
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
                            <CardTitle class="text-lg">Progreso de Configuración</CardTitle>
                            <CardDescription> Define la estructura del conjunto paso a paso </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">{{ formProgress }}% completado</span>
                            <Progress :value="formProgress" class="w-32" />
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Step Navigation -->
            <div class="mb-8 grid grid-cols-3 gap-4">
                <div
                    v-for="(step, index) in steps"
                    :key="step.id"
                    @click="goToStep(index)"
                    :class="[
                        'relative cursor-pointer rounded-lg border p-4 transition-all duration-200',
                        {
                            'border-primary bg-primary text-primary-foreground': currentStep === index,
                            'border-green-200 bg-green-50 text-green-800': index < currentStep,
                            'border-muted bg-muted/50 text-muted-foreground': index > currentStep,
                            'hover:bg-muted/80': true,
                        },
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-8 w-8 items-center justify-center rounded-full',
                                {
                                    'bg-primary-foreground text-primary': currentStep === index,
                                    'bg-green-500 text-white': index < currentStep,
                                    'bg-muted text-muted-foreground': index > currentStep,
                                },
                            ]"
                        >
                            <CheckCircle v-if="index < currentStep" class="h-4 w-4" />
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
                                    <Building class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ form.name || 'Nombre del Conjunto' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ form.number_of_towers }} torres</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Home class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">{{ totalApartments }} apartamentos</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ form.floors_per_tower }} pisos × {{ form.apartments_per_floor }} aptos/piso
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Calculator class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">{{ form.apartment_types.length }} tipos</p>
                                        <p class="text-xs text-muted-foreground">Tipos definidos</p>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <p class="text-sm font-medium">Torres configuradas</p>
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="tower in form.tower_names" :key="tower" variant="outline" class="text-xs">
                                        {{ tower }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm font-medium">Tipos de apartamento</p>
                                <div class="space-y-1">
                                    <div v-for="type in form.apartment_types" :key="type.name" class="text-xs text-muted-foreground">
                                        {{ type.name }} ({{ type.area_sqm }}m²)
                                    </div>
                                    <p v-if="form.apartment_types.length === 0" class="text-xs text-muted-foreground">Sin tipos definidos</p>
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
                                    <Building class="h-5 w-5" />
                                    Información Básica
                                </CardTitle>
                                <CardDescription> Define los datos generales del conjunto residencial </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="space-y-2">
                                    <Label for="name">Nombre del Conjunto *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Ej: Torres de Villa Campestre"
                                        :class="{ 'border-red-500': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Descripción detallada del conjunto residencial..."
                                        :class="{ 'border-red-500': form.errors.description }"
                                        rows="3"
                                    />
                                    <p v-if="form.errors.description" class="text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <Label for="number_of_towers">Número de Torres *</Label>
                                        <Input
                                            id="number_of_towers"
                                            v-model.number="form.number_of_towers"
                                            type="number"
                                            min="1"
                                            max="20"
                                            :class="{ 'border-red-500': form.errors.number_of_towers }"
                                        />
                                        <p v-if="form.errors.number_of_towers" class="text-sm text-red-600">
                                            {{ form.errors.number_of_towers }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="floors_per_tower">Pisos por Torre *</Label>
                                        <Input
                                            id="floors_per_tower"
                                            v-model.number="form.floors_per_tower"
                                            type="number"
                                            min="1"
                                            max="50"
                                            :class="{ 'border-red-500': form.errors.floors_per_tower }"
                                        />
                                        <p v-if="form.errors.floors_per_tower" class="text-sm text-red-600">
                                            {{ form.errors.floors_per_tower }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="apartments_per_floor">Apartamentos por Piso *</Label>
                                        <Input
                                            id="apartments_per_floor"
                                            v-model.number="form.apartments_per_floor"
                                            type="number"
                                            min="1"
                                            max="10"
                                            :class="{ 'border-red-500': form.errors.apartments_per_floor }"
                                        />
                                        <p v-if="form.errors.apartments_per_floor" class="text-sm text-red-600">
                                            {{ form.errors.apartments_per_floor }}
                                        </p>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-muted/50 p-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        <Info class="h-4 w-4 text-blue-600" />
                                        <p class="text-sm font-medium">Resumen de Configuración</p>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        Se generarán <strong>{{ totalApartments }} apartamentos</strong> en total ({{ form.number_of_towers }} torres
                                        × {{ form.floors_per_tower }} pisos × {{ form.apartments_per_floor }} apartamentos)
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 2: Tower Configuration -->
                        <Card v-if="currentStep === 1">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Home class="h-5 w-5" />
                                    Configuración de Torres
                                </CardTitle>
                                <CardDescription> Define los nombres de las torres del conjunto </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="space-y-4">
                                    <Label>Nombres de Torres</Label>
                                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                        <div v-for="(tower, index) in form.tower_names" :key="index" class="space-y-2">
                                            <Label :for="`tower_${index}`">Torre {{ index + 1 }}</Label>
                                            <Input
                                                :id="`tower_${index}`"
                                                v-model="form.tower_names[index]"
                                                :placeholder="`Torre ${String.fromCharCode(65 + index)}`"
                                            />
                                        </div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Personaliza los nombres de cada torre. Por defecto se usan letras (A, B, C, etc.)
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 3: Apartment Types -->
                        <Card v-if="currentStep === 2">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Tipos de Apartamento
                                </CardTitle>
                                <CardDescription> Define los diferentes tipos de apartamento y sus características </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-muted-foreground">Define al menos un tipo de apartamento para continuar</p>
                                    <Button type="button" @click="addApartmentType" class="gap-2">
                                        <Plus class="h-4 w-4" />
                                        Agregar Tipo
                                    </Button>
                                </div>

                                <div v-if="form.apartment_types.length === 0" class="py-8 text-center">
                                    <Home class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 class="mb-2 text-lg font-medium">Sin tipos de apartamento</h3>
                                    <p class="mb-4 text-muted-foreground">Agrega al menos un tipo de apartamento para continuar</p>
                                    <Button @click="addApartmentType" class="gap-2">
                                        <Plus class="h-4 w-4" />
                                        Agregar Primer Tipo
                                    </Button>
                                </div>

                                <div v-else class="space-y-6">
                                    <div v-for="(type, index) in form.apartment_types" :key="index" class="space-y-4 rounded-lg border p-6">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium">Tipo de Apartamento {{ index + 1 }}</h4>
                                            <Button type="button" variant="destructive" size="sm" @click="removeApartmentType(index)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label>Nombre del Tipo</Label>
                                                <Input v-model="type.name" placeholder="Ej: Tipo A" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Área (m²)</Label>
                                                <Input v-model.number="type.area_sqm" type="number" min="1" placeholder="86" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label>Descripción</Label>
                                            <Textarea v-model="type.description" placeholder="Descripción del tipo de apartamento..." rows="2" />
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div class="space-y-2">
                                                <Label>Habitaciones</Label>
                                                <Input v-model.number="type.bedrooms" type="number" min="0" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Baños</Label>
                                                <Input v-model.number="type.bathrooms" type="number" min="0" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Coeficiente</Label>
                                                <Input
                                                    v-model.number="type.coefficient"
                                                    type="number"
                                                    step="0.000001"
                                                    min="0"
                                                    max="1"
                                                    placeholder="0.014"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Tarifa Administración</Label>
                                                <Input v-model.number="type.administration_fee" type="number" min="0" />
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <Label>Características</Label>
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                                <div class="flex items-center space-x-2">
                                                    <Switch :id="`balcony_${index}`" v-model:checked="type.has_balcony" />
                                                    <Label :for="`balcony_${index}`">Balcón</Label>
                                                </div>

                                                <div class="flex items-center space-x-2">
                                                    <Switch :id="`laundry_${index}`" v-model:checked="type.has_laundry_room" />
                                                    <Label :for="`laundry_${index}`">Cuarto de Lavado</Label>
                                                </div>

                                                <div class="flex items-center space-x-2">
                                                    <Switch :id="`maid_${index}`" v-model:checked="type.has_maid_room" />
                                                    <Label :for="`maid_${index}`">Cuarto de Servicio</Label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label>Posiciones en el Piso</Label>
                                            <div class="flex flex-wrap gap-2">
                                                <div
                                                    v-for="position in form.apartments_per_floor"
                                                    :key="position"
                                                    class="flex items-center space-x-2"
                                                >
                                                    <input
                                                        :id="`position_${index}_${position}`"
                                                        :checked="type.floor_positions.includes(position)"
                                                        type="checkbox"
                                                        class="rounded"
                                                        @change="updateFloorPositions(index, position, ($event.target as HTMLInputElement).checked)"
                                                    />
                                                    <Label :for="`position_${index}_${position}`"> Posición {{ position }} </Label>
                                                </div>
                                            </div>
                                            <p class="text-xs text-muted-foreground">
                                                Selecciona en qué posiciones del piso se ubica este tipo de apartamento
                                            </p>
                                        </div>
                                    </div>
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
                                    {{ form.processing ? 'Creando...' : 'Crear Conjunto' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
