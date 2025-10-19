<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Building2, Plus, Trash2, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface ConjuntoConfig {
    id: number;
    name: string;
}

const props = defineProps<{
    conjuntoConfig: ConjuntoConfig;
    assetTypes: Record<string, string>;
}>();

const form = useForm({
    conjunto_config_id: props.conjuntoConfig.id,
    name: '',
    description: '',
    type: '',
    availability_rules: {
        allowed_days: [] as number[],
        time_slots: [] as Array<{ start: string; end: string }>,
    },
    max_reservations_per_user: 1,
    reservation_duration_minutes: 120,
    advance_booking_days: 30,
    reservation_cost: 0,
    requires_approval: false,
    is_active: true,
    image: null as File | null,
    metadata: {},
});

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const daysOfWeek = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
];

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const toggleDay = (dayValue: number) => {
    const index = form.availability_rules.allowed_days.indexOf(dayValue);
    if (index > -1) {
        form.availability_rules.allowed_days.splice(index, 1);
    } else {
        form.availability_rules.allowed_days.push(dayValue);
    }
};

const addTimeSlot = () => {
    form.availability_rules.time_slots.push({ start: '09:00', end: '17:00' });
};

const removeTimeSlot = (index: number) => {
    form.availability_rules.time_slots.splice(index, 1);
};

const formatDurationOptions = [
    { value: 30, label: '30 minutos' },
    { value: 60, label: '1 hora' },
    { value: 90, label: '1.5 horas' },
    { value: 120, label: '2 horas' },
    { value: 180, label: '3 horas' },
    { value: 240, label: '4 horas' },
    { value: 360, label: '6 horas' },
    { value: 480, label: '8 horas' },
    { value: 720, label: '12 horas' },
    { value: 1440, label: '24 horas' },
];

const submit = () => {
    // Clean up empty time slots
    form.availability_rules.time_slots = form.availability_rules.time_slots.filter((slot) => slot.start && slot.end && slot.start < slot.end);

    form.post(route('reservable-assets.store'), {
        onSuccess: () => {
            router.visit(route('reservable-assets.index'));
        },
    });
};

const goBack = () => {
    router.visit(route('reservable-assets.index'));
};
</script>

<template>
    <Head title="Crear Activo Reservable" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Crear Activo Reservable</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Configura un nuevo activo que los residentes puedan reservar</p>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription> Datos generales del activo reservable </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="name">Nombre del Activo *</Label>
                                    <Input v-model="form.name" placeholder="Ej: Salón Social, Gimnasio, Piscina" required />
                                    <div v-if="form.errors.name" class="text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="type">Tipo de Activo *</Label>
                                    <Select v-model="form.type" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="(label, value) in assetTypes" :key="value" :value="value">
                                                {{ label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.type" class="text-sm text-red-600">
                                        {{ form.errors.type }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Descripción</Label>
                                <Textarea v-model="form.description" placeholder="Describe las características y amenidades del activo..." rows="3" />
                                <div v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Image Upload -->
                            <div class="space-y-2">
                                <Label>Imagen del Activo</Label>
                                <div class="mt-2">
                                    <div v-if="imagePreview" class="relative inline-block">
                                        <img :src="imagePreview" alt="Preview" class="h-32 w-32 rounded-lg border object-cover" />
                                        <button
                                            type="button"
                                            @click="removeImage"
                                            class="absolute -top-2 -right-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div v-else class="rounded-lg border-2 border-dashed border-gray-300 p-6 dark:border-gray-600">
                                        <div class="text-center">
                                            <Upload class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Haz clic para subir una imagen</p>
                                            <input ref="fileInput" type="file" accept="image/*" @change="handleImageUpload" class="hidden" />
                                            <Button type="button" variant="outline" size="sm" @click="fileInput?.click()">
                                                Seleccionar imagen
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG hasta 2MB</p>
                                <div v-if="form.errors.image" class="text-sm text-red-600">
                                    {{ form.errors.image }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Reservation Configuration -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración de Reservas</CardTitle>
                            <CardDescription> Define las reglas y límites para las reservas </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="duration">Duración de Reserva *</Label>
                                    <Select v-model="form.reservation_duration_minutes">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona la duración" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in formatDurationOptions" :key="option.value" :value="option.value.toString()">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.reservation_duration_minutes" class="text-sm text-red-600">
                                        {{ form.errors.reservation_duration_minutes }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="cost">Costo de Reserva (COP)</Label>
                                    <Input v-model="form.reservation_cost" type="number" min="0" step="1000" placeholder="0" />
                                    <p class="text-xs text-gray-500">Dejar en 0 para reservas gratuitas</p>
                                    <div v-if="form.errors.reservation_cost" class="text-sm text-red-600">
                                        {{ form.errors.reservation_cost }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="max_reservations">Máximo Reservas por Usuario</Label>
                                    <Input v-model="form.max_reservations_per_user" type="number" min="1" max="10" />
                                    <p class="text-xs text-gray-500">Número máximo de reservas activas por usuario</p>
                                    <div v-if="form.errors.max_reservations_per_user" class="text-sm text-red-600">
                                        {{ form.errors.max_reservations_per_user }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="advance_days">Días de Anticipación</Label>
                                    <Input v-model="form.advance_booking_days" type="number" min="1" max="365" />
                                    <p class="text-xs text-gray-500">Máximo de días de anticipación para reservar</p>
                                    <div v-if="form.errors.advance_booking_days" class="text-sm text-red-600">
                                        {{ form.errors.advance_booking_days }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <div class="flex items-center space-x-2">
                                    <Switch v-model="form.requires_approval" />
                                    <Label>Requiere Aprobación Administrativa</Label>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Switch v-model="form.is_active" />
                                    <Label>Activo</Label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Availability Rules -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Reglas de Disponibilidad</CardTitle>
                            <CardDescription> Define cuándo está disponible el activo para reservas (opcional) </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Days of Week -->
                            <div class="space-y-3">
                                <Label>Días de la Semana Permitidos</Label>
                                <p class="text-sm text-gray-500">Dejar vacío para permitir todos los días</p>
                                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                                    <div v-for="day in daysOfWeek" :key="day.value" class="flex items-center space-x-2">
                                        <Checkbox
                                            :checked="form.availability_rules.allowed_days.includes(day.value)"
                                            @update:checked="toggleDay(day.value)"
                                        />
                                        <Label class="text-sm">{{ day.label }}</Label>
                                    </div>
                                </div>
                            </div>

                            <!-- Time Slots -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <Label>Horarios Permitidos</Label>
                                        <p class="text-sm text-gray-500">Dejar vacío para permitir 24/7</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="addTimeSlot">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Agregar Horario
                                    </Button>
                                </div>

                                <div v-for="(slot, index) in form.availability_rules.time_slots" :key="index" class="flex items-center space-x-3">
                                    <div class="flex-1">
                                        <Input v-model="slot.start" type="time" placeholder="Hora inicio" />
                                    </div>
                                    <span class="text-gray-500">hasta</span>
                                    <div class="flex-1">
                                        <Input v-model="slot.end" type="time" placeholder="Hora fin" />
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="removeTimeSlot(index)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <Button type="button" variant="outline" @click="goBack"> Cancelar </Button>
                        <Button type="submit" :disabled="form.processing" class="inline-flex items-center">
                            <Building2 class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creando...' : 'Crear Activo' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
