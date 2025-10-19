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
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, FileText, Home, Mail, Phone, Save, Settings, Upload, User, UserCheck, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface FormData {
    document_type: string;
    document_number: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    mobile_phone: string;
    birth_date: string;
    gender: string;
    emergency_contact: string;
    apartment_id: number | null;
    resident_type: string;
    status: string;
    start_date: string;
    end_date: string;
    notes: string;
    documents: File[];
    email_notifications: boolean;
    whatsapp_notifications: boolean;
    whatsapp_number: string;
    dian_address: string;
    dian_city_id: number | null;
    dian_legal_organization_type: number | null;
    dian_tributary_regime: number | null;
    dian_tributary_liability: number | null;
}

const props = defineProps({
    apartments: Array,
});

// Form state
const form = useForm<FormData>({
    document_type: '',
    document_number: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile_phone: '',
    birth_date: '',
    gender: '',
    emergency_contact: '',
    apartment_id: null,
    resident_type: '',
    status: 'Active',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    notes: '',
    documents: [],
    email_notifications: true,
    whatsapp_notifications: false,
    whatsapp_number: '',
    dian_address: '',
    dian_city_id: null,
    dian_legal_organization_type: null,
    dian_tributary_regime: null,
    dian_tributary_liability: null,
});

// UI state
const currentStep = ref(0);
const isUnsavedChanges = ref(false);

// Form validation
const validationRules = {
    step1: ['document_type', 'document_number', 'first_name', 'last_name', 'email'],
    step2: ['apartment_id', 'resident_type', 'start_date'],
    step3: [],
    step4: [],
    step5: [],
};

const steps = [
    {
        id: 0,
        title: 'Información Personal',
        description: 'Datos básicos del residente',
        icon: User,
        fields: [
            'document_type',
            'document_number',
            'first_name',
            'last_name',
            'email',
            'phone',
            'mobile_phone',
            'birth_date',
            'gender',
            'emergency_contact',
        ],
    },
    {
        id: 1,
        title: 'Información de Residencia',
        description: 'Ubicación y tipo de residencia',
        icon: Home,
        fields: ['apartment_id', 'resident_type', 'status', 'start_date', 'end_date'],
    },
    {
        id: 2,
        title: 'Documentos',
        description: 'Archivos y documentos adicionales',
        icon: FileText,
        fields: ['documents', 'notes'],
    },
    {
        id: 3,
        title: 'Configuración DIAN',
        description: 'Datos para facturación electrónica',
        icon: Settings,
        fields: ['dian_address', 'dian_city_id', 'dian_legal_organization_type', 'dian_tributary_regime', 'dian_tributary_liability'],
    },
    {
        id: 4,
        title: 'Notificaciones',
        description: 'Preferencias de notificaciones',
        icon: Mail,
        fields: ['email_notifications', 'whatsapp_notifications', 'whatsapp_number'],
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
                if (Array.isArray(value)) return value.length > 0;
                if (typeof value === 'number') return value > 0;
                return value && value.toString().trim() !== '';
            }).length
        );
    }, 0);

    return Math.round((completedFields / totalFields) * 100);
});

const canProceedToNext = computed(() => {
    return validateStep(currentStep.value);
});

const selectedApartment = computed(() => {
    if (!form.apartment_id || !props.apartments) return null;
    return props.apartments.find((apt) => apt.id === form.apartment_id);
});

// Apartment selection state
const directApartmentNumber = ref('');
const selectedTower = ref('');
const selectedFloor = ref('');
const filteredApartments = ref([]);

// Available options for filters
const availableTowers = computed(() => {
    if (!props.apartments) return [];
    return [...new Set(props.apartments.map((apt) => apt.tower))].sort();
});

const availableFloors = computed(() => {
    if (!props.apartments || !selectedTower.value) return [];
    return [...new Set(props.apartments.filter((apt) => apt.tower === selectedTower.value).map((apt) => apt.floor))].sort((a, b) => a - b);
});

// Methods for apartment selection
const getDisplayNumber = (apartment) => {
    // For format like 4101, 4102, 1201, 1202, return the number as-is
    // since it's already in the correct format (tower + floor + position)
    return apartment.number.toString();
};

const findApartmentByNumber = () => {
    if (!directApartmentNumber.value || !props.apartments) return;

    const searchNumber = directApartmentNumber.value.trim();

    // Find apartment by exact number match or by display number
    const apartment = props.apartments.find((apt) => apt.number === searchNumber || getDisplayNumber(apt) === searchNumber);

    if (apartment) {
        form.apartment_id = apartment.id;
        selectedTower.value = apartment.tower;
        selectedFloor.value = apartment.floor;
        filterApartments();
    }
};

const filterApartments = () => {
    if (!props.apartments) return;

    let filtered = props.apartments;

    if (selectedTower.value) {
        filtered = filtered.filter((apt) => apt.tower === selectedTower.value);
    }

    if (selectedFloor.value) {
        filtered = filtered.filter((apt) => apt.floor == selectedFloor.value);
    }

    filteredApartments.value = filtered.sort((a, b) => a.number.localeCompare(b.number));

    // Update direct apartment number when selecting via filters
    if (form.apartment_id) {
        const apartment = props.apartments.find((apt) => apt.id === form.apartment_id);
        if (apartment) {
            directApartmentNumber.value = apartment.number;
        }
    }
};

// Initialize filtered apartments on component mount
if (props.apartments) {
    filterApartments();
}

// Watch for changes in apartment selection
watch(
    () => form.apartment_id,
    (newId) => {
        if (newId && props.apartments) {
            const apartment = props.apartments.find((apt) => apt.id === newId);
            if (apartment) {
                directApartmentNumber.value = apartment.number;
            }
        }
    },
);

// Methods
const validateStep = (stepIndex: number): boolean => {
    const step = steps[stepIndex];
    const requiredFields = validationRules[`step${stepIndex + 1}` as keyof typeof validationRules] || [];

    return requiredFields.every((field) => {
        const value = form[field];
        if (typeof value === 'boolean') return true;
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'number') return value > 0;
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

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    form.documents = [...form.documents, ...files];
    isUnsavedChanges.value = true;
};

const removeFile = (index: number) => {
    form.documents.splice(index, 1);
    isUnsavedChanges.value = true;
};

const submit = () => {
    if (validateStep(currentStep.value)) {
        form.post(route('residents.store'), {
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
    { title: 'Residentes', href: '/residents' },
    { title: 'Nuevo Residente', href: '/residents/create' },
];

// Document types
const documentTypes = [
    { value: 'CC', label: 'Cédula de Ciudadanía' },
    { value: 'CE', label: 'Cédula de Extranjería' },
    { value: 'TI', label: 'Tarjeta de Identidad' },
    { value: 'PP', label: 'Pasaporte' },
];

// Gender options
const genderOptions = [
    { value: 'M', label: 'Masculino' },
    { value: 'F', label: 'Femenino' },
    { value: 'Other', label: 'Otro' },
];

// Resident types
const residentTypes = [
    { value: 'Owner', label: 'Propietario', description: 'Propietario del inmueble' },
    { value: 'Tenant', label: 'Arrendatario', description: 'Inquilino o arrendatario' },
    { value: 'Family', label: 'Familiar', description: 'Familiar del propietario' },
];

// Status options
const statusOptions = [
    { value: 'Active', label: 'Activo', color: 'bg-green-100 text-green-800' },
    { value: 'Inactive', label: 'Inactivo', color: 'bg-red-100 text-red-800' },
];
</script>

<template>
    <Head title="Nuevo Residente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nuevo Residente</h1>
                    <p class="text-muted-foreground">Registra un nuevo residente en el sistema</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/residents">
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

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <!-- Progress Overview -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">Progreso del Formulario</CardTitle>
                            <CardDescription> Completa todos los campos requeridos para registrar el residente </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">{{ formProgress }}% completado</span>
                            <Progress :value="formProgress" class="w-32" />
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Step Navigation -->
            <div class="mb-8 grid grid-cols-5 gap-4">
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
                                    <User class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">{{ form.first_name || 'Nombre' }} {{ form.last_name || 'Apellido' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ form.document_type }} {{ form.document_number || 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Mail class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">{{ form.email || 'email@ejemplo.com' }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Home class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">
                                        {{ selectedApartment?.full_address || 'Apartamento no seleccionado' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <UserCheck class="h-4 w-4 text-muted-foreground" />
                                    <Badge :variant="form.resident_type ? 'default' : 'secondary'">
                                        {{ residentTypes.find((t) => t.value === form.resident_type)?.label || 'Tipo' }}
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
                        <!-- Step 1: Personal Information -->
                        <Card v-if="currentStep === 0">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <User class="h-5 w-5" />
                                    Información Personal
                                </CardTitle>
                                <CardDescription> Ingresa los datos básicos del residente </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Document -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="document_type">Tipo de Documento *</Label>
                                        <Select v-model="form.document_type">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona el tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="type in documentTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.document_type" class="text-sm text-red-600">
                                            {{ form.errors.document_type }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="document_number">Número de Documento *</Label>
                                        <Input
                                            id="document_number"
                                            v-model="form.document_number"
                                            placeholder="Ingresa el número"
                                            :class="{ 'border-red-500': form.errors.document_number }"
                                        />
                                        <p v-if="form.errors.document_number" class="text-sm text-red-600">
                                            {{ form.errors.document_number }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Name -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="first_name">Nombres *</Label>
                                        <Input
                                            id="first_name"
                                            v-model="form.first_name"
                                            placeholder="Nombres completos"
                                            :class="{ 'border-red-500': form.errors.first_name }"
                                        />
                                        <p v-if="form.errors.first_name" class="text-sm text-red-600">
                                            {{ form.errors.first_name }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="last_name">Apellidos *</Label>
                                        <Input
                                            id="last_name"
                                            v-model="form.last_name"
                                            placeholder="Apellidos completos"
                                            :class="{ 'border-red-500': form.errors.last_name }"
                                        />
                                        <p v-if="form.errors.last_name" class="text-sm text-red-600">
                                            {{ form.errors.last_name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Contact -->
                                <div class="space-y-2">
                                    <Label for="email">Correo Electrónico *</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="correo@ejemplo.com"
                                        :class="{ 'border-red-500': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="phone">Teléfono</Label>
                                        <Input
                                            id="phone"
                                            v-model="form.phone"
                                            placeholder="(601) 123-4567"
                                            :class="{ 'border-red-500': form.errors.phone }"
                                        />
                                        <p v-if="form.errors.phone" class="text-sm text-red-600">
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="mobile_phone">Celular</Label>
                                        <Input
                                            id="mobile_phone"
                                            v-model="form.mobile_phone"
                                            placeholder="(300) 123-4567"
                                            :class="{ 'border-red-500': form.errors.mobile_phone }"
                                        />
                                        <p v-if="form.errors.mobile_phone" class="text-sm text-red-600">
                                            {{ form.errors.mobile_phone }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Personal details -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="birth_date">Fecha de Nacimiento</Label>
                                        <Input
                                            id="birth_date"
                                            v-model="form.birth_date"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.birth_date }"
                                        />
                                        <p v-if="form.errors.birth_date" class="text-sm text-red-600">
                                            {{ form.errors.birth_date }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="gender">Género</Label>
                                        <Select v-model="form.gender">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona el género" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="gender in genderOptions" :key="gender.value" :value="gender.value">
                                                    {{ gender.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.gender" class="text-sm text-red-600">
                                            {{ form.errors.gender }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Emergency contact -->
                                <div class="space-y-2">
                                    <Label for="emergency_contact">Contacto de Emergencia</Label>
                                    <Textarea
                                        id="emergency_contact"
                                        v-model="form.emergency_contact"
                                        placeholder="Nombre, teléfono y relación del contacto de emergencia"
                                        :class="{ 'border-red-500': form.errors.emergency_contact }"
                                        rows="3"
                                    />
                                    <p v-if="form.errors.emergency_contact" class="text-sm text-red-600">
                                        {{ form.errors.emergency_contact }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 2: Residence Information -->
                        <Card v-if="currentStep === 1">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Home class="h-5 w-5" />
                                    Información de Residencia
                                </CardTitle>
                                <CardDescription> Detalles sobre la ubicación y tipo de residencia </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Apartment Selection -->
                                <div class="space-y-4">
                                    <Label>Selección de Apartamento *</Label>

                                    <!-- Direct apartment number input -->
                                    <div class="space-y-2">
                                        <Label for="direct_apartment">Número de Apartamento</Label>
                                        <Input
                                            id="direct_apartment"
                                            v-model="directApartmentNumber"
                                            placeholder="Ej: 1101, 2305, etc."
                                            @input="findApartmentByNumber"
                                            class="font-mono"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Ingresa el número completo del apartamento (1101 = Torre 1, Piso 1, Apto 1)
                                        </p>
                                    </div>

                                    <!-- Or tower/floor selection -->
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-2">
                                            <div class="h-px flex-1 bg-border"></div>
                                            <span class="text-sm text-muted-foreground">O selecciona por torre y piso</span>
                                            <div class="h-px flex-1 bg-border"></div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4">
                                            <div class="space-y-2">
                                                <Label for="tower_filter">Torre</Label>
                                                <Select v-model="selectedTower" @update:model-value="filterApartments">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Torre" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="tower in availableTowers" :key="tower" :value="tower">
                                                            Torre {{ tower }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="floor_filter">Piso</Label>
                                                <Select v-model="selectedFloor" @update:model-value="filterApartments" :disabled="!selectedTower">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Piso" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="floor in availableFloors" :key="floor" :value="floor">
                                                            Piso {{ floor }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="apartment_filter">Apartamento</Label>
                                                <Select v-model="form.apartment_id" :disabled="!selectedFloor">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Apto" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="apartment in filteredApartments" :key="apartment.id" :value="apartment.id">
                                                            <div class="flex flex-col">
                                                                <span>{{ getDisplayNumber(apartment) }}</span>
                                                                <span class="text-xs text-muted-foreground">
                                                                    {{ apartment.apartment_type?.name }}
                                                                </span>
                                                            </div>
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected apartment info -->
                                    <div v-if="selectedApartment" class="rounded-lg bg-muted p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">{{ selectedApartment.full_address }}</p>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ selectedApartment.apartment_type?.display_name }} - ${{
                                                        selectedApartment.apartment_type?.administration_fee?.toLocaleString()
                                                    }}/mes
                                                </p>
                                            </div>
                                            <Badge :variant="selectedApartment.status === 'Available' ? 'secondary' : 'default'">
                                                {{ selectedApartment.status }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <p v-if="form.errors.apartment_id" class="text-sm text-red-600">
                                        {{ form.errors.apartment_id }}
                                    </p>
                                </div>

                                <!-- Resident type -->
                                <div class="space-y-2">
                                    <Label for="resident_type">Tipo de Residente *</Label>
                                    <Select v-model="form.resident_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona el tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="type in residentTypes" :key="type.value" :value="type.value">
                                                <div class="flex flex-col">
                                                    <span>{{ type.label }} - {{ type.description }}</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.resident_type" class="text-sm text-red-600">
                                        {{ form.errors.resident_type }}
                                    </p>
                                </div>

                                <!-- Status -->
                                <div class="space-y-2">
                                    <Label for="status">Estado *</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona el estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                                <div class="flex items-center gap-2">
                                                    <div :class="['h-2 w-2 rounded-full', status.color]" />
                                                    {{ status.label }}
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.status" class="text-sm text-red-600">
                                        {{ form.errors.status }}
                                    </p>
                                </div>

                                <!-- Dates -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="start_date">Fecha de Inicio *</Label>
                                        <Input
                                            id="start_date"
                                            v-model="form.start_date"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.start_date }"
                                        />
                                        <p v-if="form.errors.start_date" class="text-sm text-red-600">
                                            {{ form.errors.start_date }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="end_date">Fecha de Fin</Label>
                                        <Input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.end_date }"
                                        />
                                        <p class="text-xs text-muted-foreground">Deja en blanco si no hay fecha de fin</p>
                                        <p v-if="form.errors.end_date" class="text-sm text-red-600">
                                            {{ form.errors.end_date }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 3: Documents -->
                        <Card v-if="currentStep === 2">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Documentos y Notas
                                </CardTitle>
                                <CardDescription> Sube documentos relevantes y agrega notas adicionales </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- File upload -->
                                <div class="space-y-2">
                                    <Label for="documents">Documentos</Label>
                                    <div class="rounded-lg border-2 border-dashed p-8 text-center transition-colors hover:border-primary/50">
                                        <Upload class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                        <div class="space-y-2">
                                            <Label for="documents" class="cursor-pointer">
                                                <span class="font-medium text-primary hover:text-primary/80"> Haz clic para subir archivos </span>
                                                <input
                                                    id="documents"
                                                    type="file"
                                                    multiple
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                    class="sr-only"
                                                    @change="handleFileUpload"
                                                />
                                            </Label>
                                            <p class="text-sm text-muted-foreground">PDF, DOC, DOCX, JPG, PNG hasta 10MB cada uno</p>
                                        </div>
                                    </div>

                                    <!-- Uploaded files -->
                                    <div v-if="form.documents.length > 0" class="space-y-2">
                                        <p class="text-sm font-medium">Archivos subidos:</p>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(file, index) in form.documents"
                                                :key="index"
                                                class="flex items-center justify-between rounded-lg bg-muted p-3"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <FileText class="h-5 w-5 text-muted-foreground" />
                                                    <div>
                                                        <p class="text-sm font-medium">{{ file.name }}</p>
                                                        <p class="text-xs text-muted-foreground">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                                    </div>
                                                </div>
                                                <Button type="button" variant="ghost" size="sm" @click="removeFile(index)">
                                                    <X class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="form.errors.documents" class="text-sm text-red-600">
                                        {{ form.errors.documents }}
                                    </p>
                                </div>

                                <!-- Notes -->
                                <div class="space-y-2">
                                    <Label for="notes">Notas Adicionales</Label>
                                    <Textarea
                                        id="notes"
                                        v-model="form.notes"
                                        placeholder="Información adicional sobre el residente..."
                                        :class="{ 'border-red-500': form.errors.notes }"
                                        rows="4"
                                    />
                                    <p v-if="form.errors.notes" class="text-sm text-red-600">
                                        {{ form.errors.notes }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 4: DIAN Configuration -->
                        <Card v-if="currentStep === 3">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Configuración DIAN
                                </CardTitle>
                                <CardDescription> Datos opcionales para facturación electrónica DIAN </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Address -->
                                <div class="space-y-2">
                                    <Label for="dian_address">Dirección para Factura Electrónica</Label>
                                    <Input
                                        id="dian_address"
                                        v-model="form.dian_address"
                                        placeholder="Dirección personalizada para DIAN (opcional)"
                                        :class="{ 'border-red-500': form.errors.dian_address }"
                                    />
                                    <p class="text-xs text-muted-foreground">Si no se especifica, se usará la dirección del apartamento</p>
                                    <p v-if="form.errors.dian_address" class="text-sm text-red-600">
                                        {{ form.errors.dian_address }}
                                    </p>
                                </div>

                                <!-- City ID -->
                                <div class="space-y-2">
                                    <Label for="dian_city_id">Código Ciudad DIAN</Label>
                                    <Input
                                        id="dian_city_id"
                                        v-model="form.dian_city_id"
                                        type="number"
                                        placeholder="11001 (Bogotá por defecto)"
                                        :class="{ 'border-red-500': form.errors.dian_city_id }"
                                    />
                                    <p class="text-xs text-muted-foreground">Código de municipio según DIAN (por defecto: 11001 - Bogotá)</p>
                                    <p v-if="form.errors.dian_city_id" class="text-sm text-red-600">
                                        {{ form.errors.dian_city_id }}
                                    </p>
                                </div>

                                <!-- Legal Organization Type -->
                                <div class="space-y-2">
                                    <Label for="dian_legal_organization_type">Tipo Organización Legal</Label>
                                    <Select v-model="form.dian_legal_organization_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar tipo (por defecto: Persona Natural)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="1">Persona Natural</SelectItem>
                                            <SelectItem :value="2">Persona Jurídica</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">Por defecto: Persona Natural</p>
                                    <p v-if="form.errors.dian_legal_organization_type" class="text-sm text-red-600">
                                        {{ form.errors.dian_legal_organization_type }}
                                    </p>
                                </div>

                                <!-- Tributary Regime -->
                                <div class="space-y-2">
                                    <Label for="dian_tributary_regime">Régimen Tributario</Label>
                                    <Select v-model="form.dian_tributary_regime">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar régimen (por defecto: Simplificado)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="1">Régimen Simplificado</SelectItem>
                                            <SelectItem :value="2">Régimen Común</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">Por defecto: Régimen Simplificado</p>
                                    <p v-if="form.errors.dian_tributary_regime" class="text-sm text-red-600">
                                        {{ form.errors.dian_tributary_regime }}
                                    </p>
                                </div>

                                <!-- Tributary Liability -->
                                <div class="space-y-2">
                                    <Label for="dian_tributary_liability">Responsabilidad Tributaria</Label>
                                    <Select v-model="form.dian_tributary_liability">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar responsabilidad (por defecto: No Responsable)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="1">No Responsable</SelectItem>
                                            <SelectItem :value="2">Responsable de IVA</SelectItem>
                                            <SelectItem :value="3">Agente de Retención</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">Por defecto: No Responsable</p>
                                    <p v-if="form.errors.dian_tributary_liability" class="text-sm text-red-600">
                                        {{ form.errors.dian_tributary_liability }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 5: Configuration -->
                        <Card v-if="currentStep === 4">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Mail class="h-5 w-5" />
                                    Configuración de Notificaciones
                                </CardTitle>
                                <CardDescription> Configura las preferencias de notificaciones del residente </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Email notifications -->
                                <div class="flex items-center justify-between rounded-lg border p-4">
                                    <div class="flex items-center gap-3">
                                        <Mail class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium">Notificaciones por Email</p>
                                            <p class="text-sm text-muted-foreground">Recibir notificaciones importantes por correo electrónico</p>
                                        </div>
                                    </div>
                                    <Switch
                                        v-model:checked="form.email_notifications"
                                        :class="{ 'border-red-500': form.errors.email_notifications }"
                                    />
                                </div>

                                <!-- WhatsApp notifications -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between rounded-lg border p-4">
                                        <div class="flex items-center gap-3">
                                            <Phone class="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <p class="font-medium">Notificaciones por WhatsApp</p>
                                                <p class="text-sm text-muted-foreground">Recibir notificaciones y recordatorios por WhatsApp</p>
                                            </div>
                                        </div>
                                        <Switch
                                            v-model:checked="form.whatsapp_notifications"
                                            :class="{ 'border-red-500': form.errors.whatsapp_notifications }"
                                        />
                                    </div>

                                    <div v-if="form.whatsapp_notifications" class="space-y-2 pl-12">
                                        <Label for="whatsapp_number">Número de WhatsApp</Label>
                                        <Input
                                            id="whatsapp_number"
                                            v-model="form.whatsapp_number"
                                            placeholder="+ +44 7447 313219"
                                            :class="{ 'border-red-500': form.errors.whatsapp_number }"
                                        />
                                        <p v-if="form.errors.whatsapp_number" class="text-sm text-red-600">
                                            {{ form.errors.whatsapp_number }}
                                        </p>
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
                                    {{ form.processing ? 'Guardando...' : 'Guardar Residente' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
