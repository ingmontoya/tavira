<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import ValidationErrors from '@/components/ValidationErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, FileText, Save, UserPlus, Trash2, Plus, Car } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Guest {
    guest_name: string;
    document_type: string;
    document_number: string;
    phone: string;
    vehicle_plate: string;
    vehicle_color: string;
}

interface VisitFormData {
    apartment_id: number | null;
    visit_reason: string;
    valid_from: string;
    valid_until: string;
    guests: Guest[];
}

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
}

const props = defineProps<{
    apartments: Apartment[];
}>();

const form = useForm<VisitFormData>({
    apartment_id: null,
    visit_reason: '',
    valid_from: '',
    valid_until: '',
    guests: [
        {
            guest_name: '',
            document_type: 'CC',
            document_number: '',
            phone: '',
            vehicle_plate: '',
            vehicle_color: '',
        },
    ],
});

const selectedApartment = computed(() => {
    if (!form.apartment_id) return null;
    return props.apartments.find((apt) => apt.id === form.apartment_id);
});

// Set default times (current time for start, +4 hours for end)
const setDefaultTimes = () => {
    const now = new Date();
    const fourHoursLater = new Date(now.getTime() + 4 * 60 * 60 * 1000);

    form.valid_from = now.toISOString().slice(0, 16);
    form.valid_until = fourHoursLater.toISOString().slice(0, 16);
};

// Initialize default times
setDefaultTimes();

// Watch for date changes and validate
const dateValidation = computed(() => {
    const now = new Date();
    const validFrom = new Date(form.valid_from);
    const validUntil = new Date(form.valid_until);

    const errors = [];

    if (validFrom < now) {
        errors.push('La fecha de inicio debe ser en el futuro');
    }

    if (validUntil <= validFrom) {
        errors.push('La fecha de finalización debe ser posterior a la fecha de inicio');
    }

    return {
        isValid: errors.length === 0,
        errors,
    };
});

const addGuest = () => {
    form.guests.push({
        guest_name: '',
        document_type: 'CC',
        document_number: '',
        phone: '',
        vehicle_plate: '',
        vehicle_color: '',
    });
};

const removeGuest = (index: number) => {
    if (form.guests.length > 1) {
        form.guests.splice(index, 1);
    }
};

const submit = () => {
    // Ensure dates are valid before submitting
    const now = new Date();
    const validFrom = new Date(form.valid_from);

    // If the selected start time is in the past, adjust it to now
    if (validFrom < now) {
        form.valid_from = now.toISOString().slice(0, 16);

        // Also ensure the end time is at least 1 hour after start time
        const validUntil = new Date(form.valid_until);
        const minEndTime = new Date(now.getTime() + 60 * 60 * 1000); // 1 hour later

        if (validUntil <= now) {
            form.valid_until = minEndTime.toISOString().slice(0, 16);
        }
    }

    form.post(route('visits.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Nueva Visita" />

    <AppLayout>
        <div class="container mx-auto max-w-4xl px-6 py-8">
            <div class="mb-8">
                <div class="mb-4 flex items-center gap-4">
                    <Link :href="route('visits.index')" class="text-gray-500 hover:text-gray-700">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Nueva Visita</h1>
                        <p class="text-gray-600">Registra una nueva visita con uno o más invitados y genera el código QR de acceso</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <ValidationErrors :errors="form.errors" />

                <!-- Apartment Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Información del Apartamento
                        </CardTitle>
                        <CardDescription> Selecciona el apartamento para la visita </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label for="apartment_id">Apartamento <span class="text-red-500">*</span></Label>
                            <Select v-model="form.apartment_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar apartamento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="apartment in props.apartments" :key="apartment.id" :value="apartment.id">
                                        {{ apartment.tower }}-{{ apartment.number }} (Piso {{ apartment.floor }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="selectedApartment" class="rounded-lg bg-blue-50 p-3">
                            <p class="text-sm font-medium text-blue-900">
                                Apartamento seleccionado: {{ selectedApartment.tower }}-{{ selectedApartment.number }}
                            </p>
                            <p class="text-sm text-blue-700">Torre {{ selectedApartment.tower }}, Piso {{ selectedApartment.floor }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Guests Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <UserPlus class="h-5 w-5" />
                                    Invitados
                                </CardTitle>
                                <CardDescription> Información de los visitantes ({{ form.guests.length }} invitado{{ form.guests.length !== 1 ? 's' : '' }}) </CardDescription>
                            </div>
                            <Button type="button" @click="addGuest" variant="outline" size="sm">
                                <Plus class="mr-2 h-4 w-4" />
                                Agregar Invitado
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div
                            v-for="(guest, index) in form.guests"
                            :key="index"
                            class="space-y-4 rounded-lg border p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Invitado {{ index + 1 }}</h3>
                                <Button
                                    v-if="form.guests.length > 1"
                                    type="button"
                                    @click="removeGuest(index)"
                                    variant="ghost"
                                    size="sm"
                                >
                                    <Trash2 class="h-4 w-4 text-red-600" />
                                </Button>
                            </div>

                            <div>
                                <Label :for="`guest_name_${index}`">Nombre completo <span class="text-red-500">*</span></Label>
                                <Input :id="`guest_name_${index}`" v-model="guest.guest_name" placeholder="Nombre completo del invitado" required />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label :for="`document_type_${index}`">Tipo de documento <span class="text-red-500">*</span></Label>
                                    <Select v-model="guest.document_type" required>
                                        <SelectTrigger :id="`document_type_${index}`">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="CC">Cédula de Ciudadanía</SelectItem>
                                            <SelectItem value="CE">Cédula de Extranjería</SelectItem>
                                            <SelectItem value="Pasaporte">Pasaporte</SelectItem>
                                            <SelectItem value="TI">Tarjeta de Identidad</SelectItem>
                                            <SelectItem value="Otro">Otro</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label :for="`document_number_${index}`">Número de documento <span class="text-red-500">*</span></Label>
                                    <Input :id="`document_number_${index}`" v-model="guest.document_number" placeholder="123456789" required />
                                </div>
                            </div>

                            <div>
                                <Label :for="`phone_${index}`">Teléfono</Label>
                                <Input :id="`phone_${index}`" v-model="guest.phone" placeholder="+57 300 123 4567" type="tel" />
                            </div>

                            <!-- Vehicle Info -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center gap-2">
                                    <Car class="h-4 w-4 text-gray-500" />
                                    <Label class="mb-0 text-sm font-medium">Información del Vehículo (Opcional)</Label>
                                </div>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <Label :for="`vehicle_plate_${index}`" class="text-sm">Placa</Label>
                                        <Input
                                            :id="`vehicle_plate_${index}`"
                                            v-model="guest.vehicle_plate"
                                            placeholder="ABC123"
                                            class="uppercase"
                                        />
                                    </div>
                                    <div>
                                        <Label :for="`vehicle_color_${index}`" class="text-sm">Color</Label>
                                        <Input
                                            :id="`vehicle_color_${index}`"
                                            v-model="guest.vehicle_color"
                                            placeholder="Blanco, Negro, Rojo..."
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Visit Schedule -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Horario de Visita
                        </CardTitle>
                        <CardDescription> Define el período de validez del código QR </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="valid_from">Válida desde <span class="text-red-500">*</span></Label>
                                <Input id="valid_from" v-model="form.valid_from" type="datetime-local" required />
                            </div>

                            <div>
                                <Label for="valid_until">Válida hasta <span class="text-red-500">*</span></Label>
                                <Input id="valid_until" v-model="form.valid_until" type="datetime-local" required />
                            </div>
                        </div>

                        <div>
                            <Label for="visit_reason">Motivo de la visita</Label>
                            <Textarea
                                id="visit_reason"
                                v-model="form.visit_reason"
                                placeholder="Describe brevemente el motivo de la visita..."
                                rows="3"
                            />
                        </div>

                        <!-- Date validation warnings -->
                        <div v-if="!dateValidation.isValid" class="rounded-lg border border-red-200 bg-red-50 p-3">
                            <div class="mb-2 flex items-center gap-2 text-red-800">
                                <Clock class="h-4 w-4" />
                                <p class="text-sm font-medium">Fechas incorrectas</p>
                            </div>
                            <ul class="list-inside list-disc space-y-1 text-sm text-red-700">
                                <li v-for="error in dateValidation.errors" :key="error">{{ error }}</li>
                            </ul>
                        </div>

                        <div class="rounded-lg bg-amber-50 p-3">
                            <div class="flex items-center gap-2 text-amber-800">
                                <Clock class="h-4 w-4" />
                                <p class="text-sm font-medium">Importante</p>
                            </div>
                            <p class="mt-1 text-sm text-amber-700">
                                El código QR solo será válido durante el período especificado. Una vez utilizado, el código no podrá ser reutilizado.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex justify-end gap-4 pt-6">
                    <Link :href="route('visits.index')">
                        <Button type="button" variant="outline"> Cancelar </Button>
                    </Link>
                    <Button
                        type="submit"
                        :disabled="form.processing || !dateValidation.isValid"
                        class="bg-primary"
                        :class="{ 'cursor-not-allowed opacity-50': !dateValidation.isValid }"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Creando...' : 'Crear Visita' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
