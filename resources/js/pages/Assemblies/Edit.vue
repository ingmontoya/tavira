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
import { ArrowLeft, Calendar, CheckCircle, FileText, Save, Settings, Upload, Users, Vote, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Assembly {
    id: number;
    title: string;
    description: string;
    type: string;
    status: string;
    scheduled_at: string;
    required_quorum_percentage: number;
    documents?: any[];
    meeting_notes?: string;
    metadata?: {
        location?: string;
        agenda?: string[];
        organizer?: string;
        max_duration_hours?: number;
        notification_settings?: {
            email_reminder?: boolean;
            whatsapp_reminder?: boolean;
            reminder_hours_before?: number;
        };
    };
}

interface FormData {
    title: string;
    description: string;
    type: string;
    scheduled_at: string;
    required_quorum_percentage: number;
    documents: File[];
    meeting_notes: string;
    metadata: {
        location?: string;
        agenda?: string[];
        organizer?: string;
        max_duration_hours?: number;
        notification_settings?: {
            email_reminder?: boolean;
            whatsapp_reminder?: boolean;
            reminder_hours_before?: number;
        };
    };
}

const props = defineProps<{
    assembly: Assembly;
}>();

// Form state
const form = useForm<FormData>({
    title: props.assembly.title,
    description: props.assembly.description,
    type: props.assembly.type,
    scheduled_at: props.assembly.scheduled_at,
    required_quorum_percentage: props.assembly.required_quorum_percentage,
    documents: [],
    meeting_notes: props.assembly.meeting_notes || '',
    metadata: {
        location: props.assembly.metadata?.location || '',
        agenda: props.assembly.metadata?.agenda || [],
        organizer: props.assembly.metadata?.organizer || '',
        max_duration_hours: props.assembly.metadata?.max_duration_hours || 4,
        notification_settings: {
            email_reminder: props.assembly.metadata?.notification_settings?.email_reminder ?? true,
            whatsapp_reminder: props.assembly.metadata?.notification_settings?.whatsapp_reminder ?? false,
            reminder_hours_before: props.assembly.metadata?.notification_settings?.reminder_hours_before || 24,
        },
    },
});

// UI state
const currentStep = ref(0);
const isUnsavedChanges = ref(false);
const agendaItem = ref('');

// Form validation
const validationRules = {
    step1: ['title', 'description', 'type', 'scheduled_at'],
    step2: ['required_quorum_percentage'],
    step3: [],
    step4: [],
};

const steps = [
    {
        id: 0,
        title: 'Información Básica',
        description: 'Datos generales de la asamblea',
        icon: Calendar,
        fields: ['title', 'description', 'type', 'scheduled_at'],
    },
    {
        id: 1,
        title: 'Configuración',
        description: 'Configuración de quórum y duración',
        icon: Settings,
        fields: ['required_quorum_percentage', 'max_duration_hours', 'location', 'organizer'],
    },
    {
        id: 2,
        title: 'Agenda y Documentos',
        description: 'Orden del día y archivos adjuntos',
        icon: FileText,
        fields: ['agenda', 'documents', 'meeting_notes'],
    },
    {
        id: 3,
        title: 'Notificaciones',
        description: 'Configuración de recordatorios',
        icon: Users,
        fields: ['email_reminder', 'whatsapp_reminder', 'reminder_hours_before'],
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
                if (field === 'agenda') return form.metadata.agenda && form.metadata.agenda.length > 0;
                if (field === 'documents') return form.documents.length > 0;
                if (field.includes('reminder') || field.includes('notification')) {
                    return true; // These are boolean/number fields with defaults
                }

                const value = field.includes('_') ? form.metadata[field.split('_')[0]] || form[field] : form[field] || form.metadata[field];

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

const canEdit = computed(() => {
    return props.assembly.status === 'scheduled';
});

// Methods
const validateStep = (stepIndex: number): boolean => {
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

const addAgendaItem = () => {
    if (agendaItem.value.trim()) {
        if (!form.metadata.agenda) {
            form.metadata.agenda = [];
        }
        form.metadata.agenda.push(agendaItem.value.trim());
        agendaItem.value = '';
        isUnsavedChanges.value = true;
    }
};

const removeAgendaItem = (index: number) => {
    form.metadata.agenda?.splice(index, 1);
    isUnsavedChanges.value = true;
};

const submit = () => {
    if (validateStep(currentStep.value)) {
        form.put(route('assemblies.update', props.assembly.id), {
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
    agendaItem.value = '';
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
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Editar', href: `/assemblies/${props.assembly.id}/edit` },
];

// Assembly types
const assemblyTypes = [
    { value: 'ordinary', label: 'Ordinaria', description: 'Asamblea ordinaria programada' },
    { value: 'extraordinary', label: 'Extraordinaria', description: 'Asamblea extraordinaria por temas urgentes' },
    { value: 'budget', label: 'Presupuestal', description: 'Aprobación de presupuesto anual' },
    { value: 'other', label: 'Otra', description: 'Otro tipo de asamblea' },
];

// Status badge
const statusBadge = computed(() => {
    const statusMap = {
        scheduled: { text: 'Programada', class: 'bg-blue-100 text-blue-800' },
        in_progress: { text: 'En Curso', class: 'bg-green-100 text-green-800' },
        closed: { text: 'Cerrada', class: 'bg-gray-100 text-gray-800' },
        cancelled: { text: 'Cancelada', class: 'bg-red-100 text-red-800' },
    };
    return statusMap[props.assembly.status] || { text: 'Unknown', class: 'bg-gray-100 text-gray-800' };
});

// Minimum date (today or current scheduled date, whichever is earlier)
const minDateTime = computed(() => {
    const now = new Date();
    const scheduledDate = new Date(props.assembly.scheduled_at);
    const minDate = scheduledDate < now ? scheduledDate : now;
    return minDate.toISOString().slice(0, 16);
});
</script>

<template>
    <Head :title="`Editar: ${assembly.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Editar Asamblea</h1>
                        <Badge :class="statusBadge.class">
                            {{ statusBadge.text }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">{{ assembly.title }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="`/assemblies/${assembly.id}`">
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
                                <AlertDialogAction @click="resetForm">Descartar cambios</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Warning for non-editable status -->
            <Card v-if="!canEdit" class="mb-8 border-amber-200 bg-amber-50">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-amber-100 p-2">
                            <Settings class="h-5 w-5 text-amber-600" />
                        </div>
                        <div>
                            <h3 class="font-medium text-amber-800">Edición limitada</h3>
                            <p class="text-sm text-amber-600">
                                Esta asamblea está en estado "{{ statusBadge.text }}" y solo permite ediciones limitadas. Algunos campos pueden estar
                                bloqueados.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <!-- Progress Overview -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">Progreso del Formulario</CardTitle>
                            <CardDescription> Completa todos los campos requeridos para actualizar la asamblea </CardDescription>
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
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">{{ form.title || 'Título de la asamblea' }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ assemblyTypes.find((t) => t.value === form.type)?.label || 'Tipo' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Vote class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">Quórum: {{ form.required_quorum_percentage }}%</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <p class="text-sm">
                                        {{ form.scheduled_at ? new Date(form.scheduled_at).toLocaleString('es-CO') : 'Fecha no seleccionada' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    <Badge :variant="form.metadata.agenda?.length ? 'default' : 'secondary'">
                                        {{ form.metadata.agenda?.length || 0 }} puntos de agenda
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
                                    <Calendar class="h-5 w-5" />
                                    Información Básica
                                </CardTitle>
                                <CardDescription>Edita los datos básicos de la asamblea</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Title -->
                                <div class="space-y-2">
                                    <Label for="title">Título *</Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Asamblea Ordinaria - Marzo 2024"
                                        :class="{ 'border-red-500': form.errors.title }"
                                        :disabled="!canEdit"
                                    />
                                    <p v-if="form.errors.title" class="text-sm text-red-600">
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <Label for="description">Descripción *</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Descripción del propósito y objetivos de la asamblea..."
                                        :class="{ 'border-red-500': form.errors.description }"
                                        :disabled="!canEdit"
                                        rows="3"
                                    />
                                    <p v-if="form.errors.description" class="text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </p>
                                </div>

                                <!-- Type and Date -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="type">Tipo de Asamblea *</Label>
                                        <Select v-model="form.type" :disabled="!canEdit">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona el tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="type in assemblyTypes" :key="type.value" :value="type.value">
                                                    <div class="flex flex-col">
                                                        <span>{{ type.label }}</span>
                                                        <span class="text-xs text-muted-foreground">{{ type.description }}</span>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.type" class="text-sm text-red-600">
                                            {{ form.errors.type }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="scheduled_at">Fecha y Hora *</Label>
                                        <Input
                                            id="scheduled_at"
                                            v-model="form.scheduled_at"
                                            type="datetime-local"
                                            :min="minDateTime"
                                            :class="{ 'border-red-500': form.errors.scheduled_at }"
                                            :disabled="!canEdit"
                                        />
                                        <p v-if="form.errors.scheduled_at" class="text-sm text-red-600">
                                            {{ form.errors.scheduled_at }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 2: Configuration -->
                        <Card v-if="currentStep === 1">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    Configuración
                                </CardTitle>
                                <CardDescription>Configuración de quórum y otros parámetros</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Quorum -->
                                <div class="space-y-2">
                                    <Label for="required_quorum_percentage">Quórum Requerido (%) *</Label>
                                    <Input
                                        id="required_quorum_percentage"
                                        v-model.number="form.required_quorum_percentage"
                                        type="number"
                                        min="1"
                                        max="100"
                                        placeholder="50"
                                        :class="{ 'border-red-500': form.errors.required_quorum_percentage }"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Porcentaje de apartamentos que deben participar para tener quórum válido
                                    </p>
                                    <p v-if="form.errors.required_quorum_percentage" class="text-sm text-red-600">
                                        {{ form.errors.required_quorum_percentage }}
                                    </p>
                                </div>

                                <!-- Location and Duration -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="location">Ubicación</Label>
                                        <Input id="location" v-model="form.metadata.location" placeholder="Salón comunal del conjunto" />
                                        <p class="text-xs text-muted-foreground">Lugar donde se realizará la asamblea</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="max_duration_hours">Duración Máxima (horas)</Label>
                                        <Input
                                            id="max_duration_hours"
                                            v-model.number="form.metadata.max_duration_hours"
                                            type="number"
                                            min="1"
                                            max="12"
                                            placeholder="4"
                                        />
                                        <p class="text-xs text-muted-foreground">Duración estimada de la asamblea</p>
                                    </div>
                                </div>

                                <!-- Organizer -->
                                <div class="space-y-2">
                                    <Label for="organizer">Organizador/Responsable</Label>
                                    <Input id="organizer" v-model="form.metadata.organizer" placeholder="Administración del conjunto" />
                                    <p class="text-xs text-muted-foreground">Persona o entidad responsable de organizar la asamblea</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 3: Agenda and Documents -->
                        <Card v-if="currentStep === 2">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Agenda y Documentos
                                </CardTitle>
                                <CardDescription>Orden del día y documentos de apoyo</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Agenda -->
                                <div class="space-y-4">
                                    <Label>Agenda / Orden del Día</Label>

                                    <div class="flex gap-2">
                                        <Input v-model="agendaItem" placeholder="Agregar punto de agenda..." @keyup.enter="addAgendaItem" />
                                        <Button type="button" @click="addAgendaItem" :disabled="!agendaItem.trim()"> Agregar </Button>
                                    </div>

                                    <div v-if="form.metadata.agenda?.length" class="space-y-2">
                                        <p class="text-sm font-medium">Puntos de agenda:</p>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(item, index) in form.metadata.agenda"
                                                :key="index"
                                                class="flex items-center justify-between rounded-lg bg-muted p-3"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span class="text-sm font-medium">{{ index + 1 }}.</span>
                                                    <p class="text-sm">{{ item }}</p>
                                                </div>
                                                <Button type="button" variant="ghost" size="sm" @click="removeAgendaItem(index)">
                                                    <X class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- File upload -->
                                <div class="space-y-2">
                                    <Label for="documents">Documentos Nuevos</Label>
                                    <div class="rounded-lg border-2 border-dashed p-8 text-center transition-colors hover:border-primary/50">
                                        <Upload class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                        <div class="space-y-2">
                                            <Label for="documents" class="cursor-pointer">
                                                <span class="font-medium text-primary hover:text-primary/80">
                                                    Haz clic para subir archivos adicionales
                                                </span>
                                                <input
                                                    id="documents"
                                                    type="file"
                                                    multiple
                                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                                                    class="sr-only"
                                                    @change="handleFileUpload"
                                                />
                                            </Label>
                                            <p class="text-sm text-muted-foreground">
                                                PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG hasta 10MB cada uno
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Uploaded files -->
                                    <div v-if="form.documents.length > 0" class="space-y-2">
                                        <p class="text-sm font-medium">Archivos nuevos:</p>
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
                                </div>

                                <!-- Meeting notes -->
                                <div class="space-y-2">
                                    <Label for="meeting_notes">Notas Adicionales</Label>
                                    <Textarea
                                        id="meeting_notes"
                                        v-model="form.meeting_notes"
                                        placeholder="Información adicional sobre la asamblea..."
                                        rows="4"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Step 4: Notifications -->
                        <Card v-if="currentStep === 3">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5" />
                                    Configuración de Notificaciones
                                </CardTitle>
                                <CardDescription>Configuración de recordatorios para los residentes</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Email notifications -->
                                <div class="flex items-center justify-between rounded-lg border p-4">
                                    <div class="flex items-center gap-3">
                                        <Calendar class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium">Recordatorio por Email</p>
                                            <p class="text-sm text-muted-foreground">
                                                Enviar recordatorios por correo electrónico a todos los residentes
                                            </p>
                                        </div>
                                    </div>
                                    <Switch v-model:checked="form.metadata.notification_settings.email_reminder" />
                                </div>

                                <!-- WhatsApp notifications -->
                                <div class="flex items-center justify-between rounded-lg border p-4">
                                    <div class="flex items-center gap-3">
                                        <Users class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium">Recordatorio por WhatsApp</p>
                                            <p class="text-sm text-muted-foreground">Enviar recordatorios por WhatsApp (requiere integración)</p>
                                        </div>
                                    </div>
                                    <Switch v-model:checked="form.metadata.notification_settings.whatsapp_reminder" />
                                </div>

                                <!-- Reminder timing -->
                                <div class="space-y-2">
                                    <Label for="reminder_hours_before">Enviar recordatorio (horas antes)</Label>
                                    <Select v-model="form.metadata.notification_settings.reminder_hours_before">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona el tiempo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="1">1 hora antes</SelectItem>
                                            <SelectItem :value="6">6 horas antes</SelectItem>
                                            <SelectItem :value="24">1 día antes</SelectItem>
                                            <SelectItem :value="48">2 días antes</SelectItem>
                                            <SelectItem :value="72">3 días antes</SelectItem>
                                            <SelectItem :value="168">1 semana antes</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">Tiempo antes del inicio de la asamblea para enviar el recordatorio</p>
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
                                    {{ form.processing ? 'Guardando...' : 'Actualizar Asamblea' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
