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
import { ArrowLeft, Save, UserPlus, Calendar, Clock, FileText } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface VisitFormData {
    apartment_id: number | null;
    visitor_name: string;
    visitor_document_type: string;
    visitor_document_number: string;
    visitor_phone: string;
    visit_reason: string;
    valid_from: string;
    valid_until: string;
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
    visitor_name: '',
    visitor_document_type: 'CC',
    visitor_document_number: '',
    visitor_phone: '',
    visit_reason: '',
    valid_from: '',
    valid_until: '',
});

const selectedApartment = computed(() => {
    if (!form.apartment_id) return null;
    return props.apartments.find(apt => apt.id === form.apartment_id);
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
        errors
    };
});

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
        <div class="container mx-auto px-6 py-8 max-w-2xl">
            <div class="mb-8">
                <div class="flex items-center gap-4 mb-4">
                    <Link :href="route('visits.index')" class="text-gray-500 hover:text-gray-700">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Nueva Visita</h1>
                        <p class="text-gray-600">Registra una nueva visita y genera el código QR de acceso</p>
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
                        <CardDescription>
                            Selecciona el apartamento para la visita
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label for="apartment_id">Apartamento <span class="text-red-500">*</span></Label>
                            <Select v-model="form.apartment_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar apartamento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="apartment in props.apartments"
                                        :key="apartment.id"
                                        :value="apartment.id"
                                    >
                                        {{ apartment.tower }}-{{ apartment.number }} (Piso {{ apartment.floor }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div v-if="selectedApartment" class="p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm font-medium text-blue-900">
                                Apartamento seleccionado: {{ selectedApartment.tower }}-{{ selectedApartment.number }}
                            </p>
                            <p class="text-sm text-blue-700">
                                Torre {{ selectedApartment.tower }}, Piso {{ selectedApartment.floor }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Visitor Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UserPlus class="h-5 w-5" />
                            Información del Visitante
                        </CardTitle>
                        <CardDescription>
                            Datos de identificación del visitante
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label for="visitor_name">Nombre completo <span class="text-red-500">*</span></Label>
                            <Input
                                id="visitor_name"
                                v-model="form.visitor_name"
                                placeholder="Nombre completo del visitante"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="visitor_document_type">Tipo de documento <span class="text-red-500">*</span></Label>
                                <Select v-model="form.visitor_document_type" required>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="CC">Cédula de Ciudadanía</SelectItem>
                                        <SelectItem value="CE">Cédula de Extranjería</SelectItem>
                                        <SelectItem value="PP">Pasaporte</SelectItem>
                                        <SelectItem value="TI">Tarjeta de Identidad</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div>
                                <Label for="visitor_document_number">Número de documento <span class="text-red-500">*</span></Label>
                                <Input
                                    id="visitor_document_number"
                                    v-model="form.visitor_document_number"
                                    placeholder="123456789"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <Label for="visitor_phone">Teléfono</Label>
                            <Input
                                id="visitor_phone"
                                v-model="form.visitor_phone"
                                placeholder="+57 300 123 4567"
                                type="tel"
                            />
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
                    </CardContent>
                </Card>

                <!-- Visit Schedule -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Horario de Visita
                        </CardTitle>
                        <CardDescription>
                            Define el período de validez del código QR
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="valid_from">Válida desde <span class="text-red-500">*</span></Label>
                                <Input
                                    id="valid_from"
                                    v-model="form.valid_from"
                                    type="datetime-local"
                                    required
                                />
                            </div>
                            
                            <div>
                                <Label for="valid_until">Válida hasta <span class="text-red-500">*</span></Label>
                                <Input
                                    id="valid_until"
                                    v-model="form.valid_until"
                                    type="datetime-local"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Date validation warnings -->
                        <div v-if="!dateValidation.isValid" class="p-3 bg-red-50 rounded-lg border border-red-200">
                            <div class="flex items-center gap-2 text-red-800 mb-2">
                                <Clock class="h-4 w-4" />
                                <p class="text-sm font-medium">Fechas incorrectas</p>
                            </div>
                            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                <li v-for="error in dateValidation.errors" :key="error">{{ error }}</li>
                            </ul>
                        </div>

                        <div class="p-3 bg-amber-50 rounded-lg">
                            <div class="flex items-center gap-2 text-amber-800">
                                <Clock class="h-4 w-4" />
                                <p class="text-sm font-medium">Importante</p>
                            </div>
                            <p class="text-sm text-amber-700 mt-1">
                                El código QR solo será válido durante el período especificado. 
                                Una vez utilizado, el código no podrá ser reutilizado.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex justify-end gap-4 pt-6">
                    <Link :href="route('visits.index')">
                        <Button type="button" variant="outline">
                            Cancelar
                        </Button>
                    </Link>
                    <Button 
                        type="submit" 
                        :disabled="form.processing || !dateValidation.isValid" 
                        class="bg-primary"
                        :class="{ 'opacity-50 cursor-not-allowed': !dateValidation.isValid }"
                    >
                        <Save class="h-4 w-4 mr-2" />
                        {{ form.processing ? 'Creando...' : 'Crear Visita' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>