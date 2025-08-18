<script setup lang="ts">
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

export interface MaintenanceRequest {
    id: number;
    title: string;
    description: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    status: string;
    project_type: string;
    location: string | null;
    estimated_cost: number | null;
    estimated_completion_date: string | null;
    requires_council_approval: boolean;
    maintenance_category: {
        id: number;
        name: string;
        color: string;
    };
    apartment: {
        id: number;
        number: string;
        tower: string;
    } | null;
}

interface Props {
    maintenanceRequest: MaintenanceRequest;
    categories: MaintenanceCategory[];
    apartments: Apartment[];
}

const props = defineProps<Props>();

const form = useForm({
    maintenance_category_id: props.maintenanceRequest.maintenance_category.id.toString(),
    apartment_id: props.maintenanceRequest.apartment?.id.toString() || 'none',
    title: props.maintenanceRequest.title,
    description: props.maintenanceRequest.description,
    priority: props.maintenanceRequest.priority,
    status: props.maintenanceRequest.status,
    project_type: props.maintenanceRequest.project_type,
    location: props.maintenanceRequest.location || '',
    estimated_cost: props.maintenanceRequest.estimated_cost?.toString() || '',
    estimated_completion_date: props.maintenanceRequest.estimated_completion_date
        ? props.maintenanceRequest.estimated_completion_date.split('T')[0]
        : '',
    requires_council_approval: props.maintenanceRequest.requires_council_approval,
});

const selectedCategory = computed(() => {
    if (!form.maintenance_category_id) return null;
    return props.categories.find((cat) => cat.id.toString() === form.maintenance_category_id);
});

// Auto-set council approval requirement based on category
const updateRequiresApproval = () => {
    if (selectedCategory.value) {
        form.requires_council_approval = selectedCategory.value.requires_approval;
    }
};

const submit = () => {
    // Convert 'none' to empty string for backend
    const submissionData = {
        ...form.data(),
        apartment_id: form.apartment_id === 'none' ? '' : form.apartment_id,
    };

    form.transform(() => submissionData).put(route('maintenance-requests.update', props.maintenanceRequest.id));
};

const goBack = () => {
    router.visit(route('maintenance-requests.show', props.maintenanceRequest.id));
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
        title: props.maintenanceRequest.title,
        href: `/maintenance-requests/${props.maintenanceRequest.id}`,
    },
    {
        title: 'Editar',
        href: `/maintenance-requests/${props.maintenanceRequest.id}/edit`,
    },
];
</script>

<template>
    <Head title="Editar Solicitud de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Wrench class="h-6 w-6 text-blue-600" />
                    <h1 class="text-2xl font-semibold text-gray-900">Editar Solicitud de Mantenimiento</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>
            </div>

            <Card>
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

                        <!-- Título -->
                        <div class="space-y-2">
                            <Label for="title">Título *</Label>
                            <Input id="title" v-model="form.title" type="text" placeholder="Ej: Reparación de grifo en cocina" required />
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
                                placeholder="Describe detalladamente el problema o trabajo de mantenimiento requerido..."
                                rows="4"
                                required
                            />
                            <div v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Tipo de Proyecto -->
                        <div class="space-y-2">
                            <Label for="project_type">Tipo de Proyecto *</Label>
                            <Select v-model="form.project_type">
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
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Apartamento -->
                            <div class="space-y-2">
                                <Label for="apartment_id">Apartamento</Label>
                                <Select v-model="form.apartment_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar apartamento (opcional)" />
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
                                <Label for="location">Ubicación Específica</Label>
                                <Input id="location" v-model="form.location" type="text" placeholder="Ej: Cocina, baño principal, área común, etc." />
                                <div v-if="form.errors.location" class="text-sm text-red-600">
                                    {{ form.errors.location }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Costo Estimado -->
                            <div class="space-y-2">
                                <Label for="estimated_cost">Costo Estimado (COP)</Label>
                                <Input id="estimated_cost" v-model="form.estimated_cost" type="number" min="0" step="0.01" placeholder="0.00" />
                                <div v-if="form.errors.estimated_cost" class="text-sm text-red-600">
                                    {{ form.errors.estimated_cost }}
                                </div>
                            </div>

                            <!-- Fecha Estimada -->
                            <div class="space-y-2">
                                <Label for="estimated_completion_date">Fecha Estimada de Completación</Label>
                                <Input id="estimated_completion_date" v-model="form.estimated_completion_date" type="date" />
                                <div v-if="form.errors.estimated_completion_date" class="text-sm text-red-600">
                                    {{ form.errors.estimated_completion_date }}
                                </div>
                            </div>
                        </div>

                        <!-- Requiere Aprobación del Concejo -->
                        <div class="flex items-center space-x-2">
                            <Checkbox id="requires_council_approval" v-model:checked="form.requires_council_approval" />
                            <Label for="requires_council_approval" class="text-sm"> Requiere aprobación del concejo </Label>
                        </div>
                        <div v-if="selectedCategory?.requires_approval" class="text-sm text-blue-600">
                            ℹ️ Esta categoría requiere automáticamente aprobación del concejo
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-between pt-6">
                            <Button type="button" variant="outline" @click="goBack">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
