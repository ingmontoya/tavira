<script setup lang="ts">
import SetupWizard from '@/components/maintenance/SetupWizard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

export interface MaintenanceCategory {
    id: number;
    name: string;
    color: string;
    requires_approval: boolean;
}

export interface Apartment {
    id: number;
    number: string;
    tower: string;
    apartment_type: {
        id: number;
        name: string;
    };
}

export interface Provider {
    id: number;
    name: string;
    document_type: string;
    document_number: string;
    email?: string;
    phone?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
}

interface Props {
    categories: MaintenanceCategory[];
    apartments: Apartment[];
    providers: Provider[];
    hasCategoriesConfigured: boolean;
    hasStaffConfigured: boolean;
    needsSetup: boolean;
}

const props = defineProps<Props>();

const form = useForm({
    maintenance_category_id: '',
    apartment_id: '',
    title: '',
    description: '',
    priority: 'medium',
    project_type: 'internal',
    status: 'created',
    location: '',
    estimated_cost: '',
    estimated_completion_date: '',
    requires_council_approval: false,
    // Vendor fields
    supplier_id: '',
    vendor_quote_amount: '',
    vendor_quote_description: '',
    vendor_quote_valid_until: '',
    vendor_contact_name: '',
    vendor_contact_phone: '',
    vendor_contact_email: '',
    // Recurrence fields
    is_recurring: false,
    recurrence_frequency: '',
    recurrence_interval: 1,
    recurrence_start_date: '',
    recurrence_end_date: '',
    days_before_notification: 7,
});

const selectedCategory = computed(() => {
    if (!form.maintenance_category_id) return null;
    return props.categories.find((cat) => cat.id.toString() === form.maintenance_category_id);
});

const selectedProvider = computed(() => {
    if (!form.supplier_id) return null;
    return props.providers.find((provider) => provider.id.toString() === form.supplier_id);
});

const isExternalProject = computed(() => {
    return form.project_type === 'external';
});

const showVendorFields = computed(() => {
    return isExternalProject.value;
});

const showRecurrenceFields = computed(() => {
    return form.is_recurring;
});

// Auto-set council approval requirement based on category
const updateRequiresApproval = () => {
    if (selectedCategory.value) {
        form.requires_council_approval = selectedCategory.value.requires_approval;
    }
};

// Handle project type changes
const onProjectTypeChange = () => {
    if (form.project_type === 'internal') {
        // Clear vendor fields when switching to internal
        form.supplier_id = '';
        form.vendor_quote_amount = '';
        form.vendor_quote_description = '';
        form.vendor_quote_valid_until = '';
        form.vendor_contact_name = '';
        form.vendor_contact_phone = '';
        form.vendor_contact_email = '';
    }
};

// Auto-populate vendor contact info when provider is selected
const onProviderChange = () => {
    if (selectedProvider.value) {
        form.vendor_contact_name = selectedProvider.value.contact_name || selectedProvider.value.name;
        form.vendor_contact_phone = selectedProvider.value.contact_phone || selectedProvider.value.phone || '';
        form.vendor_contact_email = selectedProvider.value.contact_email || selectedProvider.value.email || '';
    } else {
        form.vendor_contact_name = '';
        form.vendor_contact_phone = '';
        form.vendor_contact_email = '';
    }
};

const submit = () => {
    // Validate recurrence fields before submission
    if (form.is_recurring && !form.recurrence_frequency) {
        // Set error manually
        form.setError('recurrence_frequency', 'La frecuencia es obligatoria para mantenimientos recurrentes');
        // Scroll to the recurrence section
        const recurrenceSection = document.querySelector('#recurrence-section');
        if (recurrenceSection) {
            recurrenceSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    // Convert 'none' to empty string for backend and clean up vendor fields
    const submissionData = {
        ...form.data(),
        apartment_id: form.apartment_id === 'none' ? '' : form.apartment_id,
        supplier_id: form.supplier_id === 'none' ? '' : form.supplier_id,
        // Ensure is_recurring is sent as boolean
        is_recurring: !!form.is_recurring,
        // Clear vendor fields if internal project
        ...(form.project_type === 'internal'
            ? {
                  supplier_id: '',
                  vendor_quote_amount: '',
                  vendor_quote_description: '',
                  vendor_quote_valid_until: '',
                  vendor_contact_name: '',
                  vendor_contact_phone: '',
                  vendor_contact_email: '',
              }
            : {}),
    };

    console.log('Submitting data:', submissionData);
    console.log('is_recurring value:', submissionData.is_recurring, typeof submissionData.is_recurring);

    form.transform(() => submissionData).post(route('maintenance-requests.store'));
};

const goBack = () => {
    router.visit(route('maintenance-requests.index'));
};

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Administración',
        href: '#',
    },
    {
        title: 'Mantenimiento',
        href: '#',
    },
    {
        title: 'Solicitudes',
        href: '/maintenance-requests',
    },
    {
        title: 'Nueva Solicitud',
        href: '/maintenance-requests/create',
    },
];
</script>

<template>
    <Head title="Nueva Solicitud de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Wrench class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Nueva Solicitud de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <!-- Setup Wizard (shown when no categories configured) -->
            <SetupWizard
                v-if="needsSetup"
                :has-categories-configured="hasCategoriesConfigured"
                :has-staff-configured="hasStaffConfigured"
                :show-staff-step="false"
            />

            <!-- Form (only shown when categories are configured) -->
            <Card v-if="!needsSetup">
                <CardHeader>
                    <CardTitle>Información de la Solicitud</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <!-- Categoría -->
                            <div class="space-y-2">
                                <Label for="maintenance_category_id">Categoría *</Label>
                                <Select v-model="form.maintenance_category_id" @update:model-value="updateRequiresApproval">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar categoría" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                            <div class="flex items-center space-x-2">
                                                <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: category.color }"></div>
                                                <span>{{ category.name }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.maintenance_category_id" class="text-sm text-red-600">
                                    {{ form.errors.maintenance_category_id }}
                                </div>
                            </div>

                            <!-- Prioridad -->
                            <div class="space-y-2">
                                <Label for="priority">Prioridad *</Label>
                                <Select v-model="form.priority">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Baja</SelectItem>
                                        <SelectItem value="medium">Media</SelectItem>
                                        <SelectItem value="high">Alta</SelectItem>
                                        <SelectItem value="critical">Crítica</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.priority" class="text-sm text-red-600">
                                    {{ form.errors.priority }}
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="space-y-2">
                                <Label for="status">Estado *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="created">Creada</SelectItem>
                                        <SelectItem value="evaluation">En Evaluación</SelectItem>
                                        <SelectItem value="budgeted">Presupuestada</SelectItem>
                                        <SelectItem value="pending_approval">Pendiente Aprobación</SelectItem>
                                        <SelectItem value="approved">Aprobada</SelectItem>
                                        <SelectItem value="assigned">Asignada</SelectItem>
                                        <SelectItem value="in_progress">En Progreso</SelectItem>
                                        <SelectItem value="completed">Completada</SelectItem>
                                        <SelectItem value="closed">Cerrada</SelectItem>
                                        <SelectItem value="rejected">Rechazada</SelectItem>
                                        <SelectItem value="suspended">Suspendida</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.status" class="text-sm text-red-600">
                                    {{ form.errors.status }}
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Proyecto -->
                        <div class="space-y-2">
                            <Label for="project_type">Tipo de Proyecto *</Label>
                            <Select v-model="form.project_type" @update:model-value="onProjectTypeChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar tipo de proyecto" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="internal">Mantenimiento Interno</SelectItem>
                                    <SelectItem value="external">Proyecto Externo / Contratista</SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.project_type" class="text-sm text-red-600">
                                {{ form.errors.project_type }}
                            </div>
                            <div v-if="isExternalProject" class="text-sm text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                Para proyectos externos, puede relacionar un proveedor y agregar información de cotización.
                            </div>
                        </div>

                        <!-- Título -->
                        <div class="space-y-2">
                            <Label for="title">Título *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Ingrese un título descriptivo para la solicitud"
                                :class="{ 'border-red-500': form.errors.title }"
                            />
                            <div v-if="form.errors.title" class="text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="space-y-2">
                            <Label for="description">Descripción *</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describa detalladamente el problema o trabajo requerido"
                                rows="4"
                                :class="{ 'border-red-500': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Apartamento -->
                            <div class="space-y-2">
                                <Label for="apartment_id">Apartamento (Opcional)</Label>
                                <Select v-model="form.apartment_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar apartamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Sin apartamento específico</SelectItem>
                                        <SelectItem v-for="apartment in apartments" :key="apartment.id" :value="apartment.id.toString()">
                                            {{ apartment.number }} - Torre {{ apartment.tower }} ({{ apartment.apartment_type.name }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.apartment_id" class="text-sm text-red-600">
                                    {{ form.errors.apartment_id }}
                                </div>
                            </div>

                            <!-- Ubicación -->
                            <div class="space-y-2">
                                <Label for="location">Ubicación</Label>
                                <Input
                                    id="location"
                                    v-model="form.location"
                                    type="text"
                                    placeholder="Ej: Piscina, Salón Social, Parqueadero"
                                    :class="{ 'border-red-500': form.errors.location }"
                                />
                                <div v-if="form.errors.location" class="text-sm text-red-600">
                                    {{ form.errors.location }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Costo Estimado -->
                            <div class="space-y-2">
                                <Label for="estimated_cost">Costo Estimado (COP)</Label>
                                <Input
                                    id="estimated_cost"
                                    v-model="form.estimated_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="{ 'border-red-500': form.errors.estimated_cost }"
                                />
                                <div v-if="form.errors.estimated_cost" class="text-sm text-red-600">
                                    {{ form.errors.estimated_cost }}
                                </div>
                            </div>

                            <!-- Fecha Estimada de Completación -->
                            <div class="space-y-2">
                                <Label for="estimated_completion_date">Fecha Estimada de Completación</Label>
                                <Input
                                    id="estimated_completion_date"
                                    v-model="form.estimated_completion_date"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.estimated_completion_date }"
                                />
                                <div v-if="form.errors.estimated_completion_date" class="text-sm text-red-600">
                                    {{ form.errors.estimated_completion_date }}
                                </div>
                            </div>
                        </div>

                        <!-- Información del Proveedor (Solo para proyectos externos) -->
                        <div v-if="showVendorFields" class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Información del Proveedor y Cotización</h3>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Proveedor -->
                                <div class="space-y-2">
                                    <Label for="supplier_id">Proveedor (Opcional)</Label>
                                    <Select v-model="form.supplier_id" @update:model-value="onProviderChange">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar proveedor" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Proveedor no registrado</SelectItem>
                                            <SelectItem v-for="provider in providers" :key="provider.id" :value="provider.id.toString()">
                                                {{ provider.name }} ({{ provider.document_type }}: {{ provider.document_number }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.supplier_id" class="text-sm text-red-600">
                                        {{ form.errors.supplier_id }}
                                    </div>
                                </div>

                                <!-- Monto de Cotización -->
                                <div class="space-y-2">
                                    <Label for="vendor_quote_amount">Monto Cotizado (COP)</Label>
                                    <Input
                                        id="vendor_quote_amount"
                                        v-model="form.vendor_quote_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        :class="{ 'border-red-500': form.errors.vendor_quote_amount }"
                                    />
                                    <div v-if="form.errors.vendor_quote_amount" class="text-sm text-red-600">
                                        {{ form.errors.vendor_quote_amount }}
                                    </div>
                                </div>
                            </div>

                            <!-- Descripción de la Cotización -->
                            <div class="space-y-2">
                                <Label for="vendor_quote_description">Descripción de la Cotización</Label>
                                <Textarea
                                    id="vendor_quote_description"
                                    v-model="form.vendor_quote_description"
                                    placeholder="Detalle del trabajo cotizado, materiales incluidos, etc."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.vendor_quote_description }"
                                />
                                <div v-if="form.errors.vendor_quote_description" class="text-sm text-red-600">
                                    {{ form.errors.vendor_quote_description }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Vigencia de la Cotización -->
                                <div class="space-y-2">
                                    <Label for="vendor_quote_valid_until">Vigencia de Cotización</Label>
                                    <Input
                                        id="vendor_quote_valid_until"
                                        v-model="form.vendor_quote_valid_until"
                                        type="date"
                                        :class="{ 'border-red-500': form.errors.vendor_quote_valid_until }"
                                    />
                                    <div v-if="form.errors.vendor_quote_valid_until" class="text-sm text-red-600">
                                        {{ form.errors.vendor_quote_valid_until }}
                                    </div>
                                </div>

                                <!-- Contacto del Proveedor -->
                                <div class="space-y-2">
                                    <Label for="vendor_contact_name">Nombre de Contacto</Label>
                                    <Input
                                        id="vendor_contact_name"
                                        v-model="form.vendor_contact_name"
                                        type="text"
                                        placeholder="Nombre del contacto"
                                        :class="{ 'border-red-500': form.errors.vendor_contact_name }"
                                    />
                                    <div v-if="form.errors.vendor_contact_name" class="text-sm text-red-600">
                                        {{ form.errors.vendor_contact_name }}
                                    </div>
                                </div>

                                <!-- Teléfono de Contacto -->
                                <div class="space-y-2">
                                    <Label for="vendor_contact_phone">Teléfono de Contacto</Label>
                                    <Input
                                        id="vendor_contact_phone"
                                        v-model="form.vendor_contact_phone"
                                        type="text"
                                        placeholder="Teléfono"
                                        :class="{ 'border-red-500': form.errors.vendor_contact_phone }"
                                    />
                                    <div v-if="form.errors.vendor_contact_phone" class="text-sm text-red-600">
                                        {{ form.errors.vendor_contact_phone }}
                                    </div>
                                </div>
                            </div>

                            <!-- Email de Contacto -->
                            <div class="space-y-2">
                                <Label for="vendor_contact_email">Email de Contacto</Label>
                                <Input
                                    id="vendor_contact_email"
                                    v-model="form.vendor_contact_email"
                                    type="email"
                                    placeholder="email@ejemplo.com"
                                    :class="{ 'border-red-500': form.errors.vendor_contact_email }"
                                />
                                <div v-if="form.errors.vendor_contact_email" class="text-sm text-red-600">
                                    {{ form.errors.vendor_contact_email }}
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de Recurrencia -->
                        <div id="recurrence-section" class="space-y-6 border-t pt-6">
                            <div class="flex items-center space-x-3">
                                <input
                                    id="is_recurring"
                                    v-model="form.is_recurring"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <Label for="is_recurring" class="text-sm font-medium cursor-pointer">
                                    Marcar como Mantenimiento Recurrente
                                </Label>
                            </div>

                            <div v-if="showRecurrenceFields" class="space-y-6">
                                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                                    <div class="flex items-start space-x-3">
                                        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-blue-900">Configuración de Recurrencia</h4>
                                            <p class="text-sm text-blue-700 mt-1">
                                                Configure la frecuencia del mantenimiento y recibirá notificaciones automáticas antes de cada ocurrencia.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Frecuencia de Recurrencia -->
                                    <div class="space-y-2">
                                        <Label for="recurrence_frequency" class="text-red-600">Frecuencia * (Requerido)</Label>
                                        <Select v-model="form.recurrence_frequency" required>
                                            <SelectTrigger :class="{ 'border-red-500': form.errors.recurrence_frequency || (!form.recurrence_frequency && form.is_recurring) }">
                                                <SelectValue placeholder="Debe seleccionar una frecuencia" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="daily">Diario</SelectItem>
                                                <SelectItem value="weekly">Semanal</SelectItem>
                                                <SelectItem value="monthly">Mensual</SelectItem>
                                                <SelectItem value="quarterly">Trimestral (cada 3 meses)</SelectItem>
                                                <SelectItem value="semi_annual">Semestral (cada 6 meses)</SelectItem>
                                                <SelectItem value="annual">Anual (cada año)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.recurrence_frequency || (!form.recurrence_frequency && form.is_recurring)" class="text-sm text-red-600">
                                            {{ form.errors.recurrence_frequency || 'La frecuencia es obligatoria para mantenimientos recurrentes' }}
                                        </div>
                                    </div>

                                    <!-- Intervalo -->
                                    <div class="space-y-2">
                                        <Label for="recurrence_interval">Cada (Intervalo)</Label>
                                        <Input
                                            id="recurrence_interval"
                                            v-model="form.recurrence_interval"
                                            type="number"
                                            min="1"
                                            placeholder="1"
                                            :class="{ 'border-red-500': form.errors.recurrence_interval }"
                                        />
                                        <div class="text-xs text-gray-500">Ej: 2 = cada 2 semanas/meses</div>
                                        <div v-if="form.errors.recurrence_interval" class="text-sm text-red-600">
                                            {{ form.errors.recurrence_interval }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Fecha de Inicio -->
                                    <div class="space-y-2">
                                        <Label for="recurrence_start_date">Fecha de Inicio *</Label>
                                        <Input
                                            id="recurrence_start_date"
                                            v-model="form.recurrence_start_date"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.recurrence_start_date }"
                                        />
                                        <div v-if="form.errors.recurrence_start_date" class="text-sm text-red-600">
                                            {{ form.errors.recurrence_start_date }}
                                        </div>
                                    </div>

                                    <!-- Fecha de Fin (Opcional) -->
                                    <div class="space-y-2">
                                        <Label for="recurrence_end_date">Fecha de Fin (Opcional)</Label>
                                        <Input
                                            id="recurrence_end_date"
                                            v-model="form.recurrence_end_date"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.recurrence_end_date }"
                                        />
                                        <div class="text-xs text-gray-500">Dejar vacío para recurrencia indefinida</div>
                                        <div v-if="form.errors.recurrence_end_date" class="text-sm text-red-600">
                                            {{ form.errors.recurrence_end_date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Días antes de notificación -->
                                <div class="space-y-2">
                                    <Label for="days_before_notification">Notificar con (días de anticipación)</Label>
                                    <Input
                                        id="days_before_notification"
                                        v-model="form.days_before_notification"
                                        type="number"
                                        min="1"
                                        max="30"
                                        placeholder="7"
                                        :class="{ 'border-red-500': form.errors.days_before_notification }"
                                    />
                                    <div class="text-xs text-gray-500">
                                        Los usuarios serán notificados este número de días antes de cada mantenimiento
                                    </div>
                                    <div v-if="form.errors.days_before_notification" class="text-sm text-red-600">
                                        {{ form.errors.days_before_notification }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requiere Aprobación del Concejo -->
                        <div class="flex items-center space-x-2">
                            <Checkbox id="requires_council_approval" v-model:checked="form.requires_council_approval" />
                            <Label for="requires_council_approval" class="text-sm leading-none font-medium">
                                Requiere aprobación del concejo de administración
                            </Label>
                        </div>
                        <div v-if="selectedCategory?.requires_approval" class="text-sm text-blue-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Esta categoría requiere automáticamente aprobación del concejo.
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-4">
                            <Button type="button" variant="outline" @click="goBack"> Cancelar </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Guardando...' : 'Crear Solicitud' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
