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
import { ArrowLeft, Save, Settings } from 'lucide-vue-next';

export interface MaintenanceCategory {
    id: number;
    name: string;
    description: string | null;
    color: string;
    priority_level: number;
    requires_approval: boolean;
    estimated_hours: number | null;
    is_active: boolean;
}

interface Props {
    category: MaintenanceCategory;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.category.name,
    description: props.category.description || '',
    color: props.category.color,
    priority_level: props.category.priority_level,
    requires_approval: props.category.requires_approval,
    estimated_hours: props.category.estimated_hours?.toString() || '',
    is_active: props.category.is_active,
});

const submit = () => {
    form.put(route('maintenance-categories.update', props.category.id));
};

const goBack = () => {
    router.visit(route('maintenance-categories.show', props.category.id));
};

const priorityOptions = [
    { value: 1, label: 'Crítica', description: 'Requiere atención inmediata' },
    { value: 2, label: 'Alta', description: 'Importante, programar pronto' },
    { value: 3, label: 'Media', description: 'Programación normal' },
    { value: 4, label: 'Baja', description: 'No urgente' },
];

const colorOptions = [
    { value: '#3B82F6', label: 'Azul' },
    { value: '#10B981', label: 'Verde' },
    { value: '#F59E0B', label: 'Naranja' },
    { value: '#EF4444', label: 'Rojo' },
    { value: '#8B5CF6', label: 'Morado' },
    { value: '#06B6D4', label: 'Cian' },
    { value: '#84CC16', label: 'Lima' },
    { value: '#F97316', label: 'Naranja Oscuro' },
    { value: '#EC4899', label: 'Rosa' },
    { value: '#6B7280', label: 'Gris' },
];

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
        title: 'Categorías',
        href: '/maintenance-categories',
    },
    {
        title: props.category.name,
        href: `/maintenance-categories/${props.category.id}`,
    },
    {
        title: 'Editar',
        href: `/maintenance-categories/${props.category.id}/edit`,
    },
];
</script>

<template>
    <Head title="Editar Categoría de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: category.color }"></div>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Editar Categoría</h1>
                        <p class="text-sm text-gray-600">{{ category.name }}</p>
                    </div>
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
                    <CardTitle class="flex items-center space-x-2">
                        <Settings class="h-5 w-5" />
                        <span>Información de la Categoría</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Nombre -->
                            <div class="space-y-2">
                                <Label for="name">Nombre *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ej: Plomería, Electricidad, Pintura"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Color -->
                            <div class="space-y-2">
                                <Label for="color">Color Identificativo *</Label>
                                <Select v-model="form.color">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar color" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
                                            <div class="flex items-center space-x-2">
                                                <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: color.value }"></div>
                                                <span>{{ color.label }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.color" class="text-sm text-red-600">
                                    {{ form.errors.color }}
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="space-y-2">
                            <Label for="description">Descripción</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe el tipo de trabajos que incluye esta categoría..."
                                rows="3"
                            />
                            <div v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Nivel de Prioridad -->
                            <div class="space-y-2">
                                <Label for="priority_level">Nivel de Prioridad *</Label>
                                <Select v-model="form.priority_level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="priority in priorityOptions" :key="priority.value" :value="priority.value">
                                            <div class="space-y-1">
                                                <div class="font-medium">{{ priority.label }}</div>
                                                <div class="text-sm text-gray-500">{{ priority.description }}</div>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.priority_level" class="text-sm text-red-600">
                                    {{ form.errors.priority_level }}
                                </div>
                            </div>

                            <!-- Horas Estimadas -->
                            <div class="space-y-2">
                                <Label for="estimated_hours">Horas Estimadas</Label>
                                <Input
                                    id="estimated_hours"
                                    v-model="form.estimated_hours"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    placeholder="Ej: 2, 4.5, 8"
                                />
                                <p class="text-sm text-gray-500">Tiempo promedio para completar trabajos de esta categoría</p>
                                <div v-if="form.errors.estimated_hours" class="text-sm text-red-600">
                                    {{ form.errors.estimated_hours }}
                                </div>
                            </div>
                        </div>

                        <!-- Configuraciones -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Configuraciones</h3>
                            
                            <div class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="requires_approval"
                                        v-model:checked="form.requires_approval"
                                    />
                                    <Label for="requires_approval" class="text-sm">
                                        Requiere aprobación del concejo automáticamente
                                    </Label>
                                </div>
                                <p class="text-sm text-gray-500 ml-6">
                                    Las solicitudes de esta categoría requerirán aprobación del concejo por defecto
                                </p>

                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        v-model:checked="form.is_active"
                                    />
                                    <Label for="is_active" class="text-sm">
                                        Categoría activa
                                    </Label>
                                </div>
                                <p class="text-sm text-gray-500 ml-6">
                                    Solo las categorías activas están disponibles para nuevas solicitudes
                                </p>
                            </div>
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