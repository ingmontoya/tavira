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

const form = useForm({
    name: '',
    description: '',
    color: '#3B82F6',
    priority_level: 3,
    requires_approval: false,
    estimated_hours: '',
});

const submit = () => {
    form.post(route('maintenance-categories.store'));
};

const goBack = () => {
    router.visit(route('maintenance-categories.index'));
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
</script>

<template>
    <Head title="Nueva Categoría de Mantenimiento" />

    <AppLayout>
        <template #header>
            <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div class="flex items-center space-x-3">
                    <Settings class="h-6 w-6 text-blue-600" />
                    <h2 class="text-xl leading-tight font-semibold text-gray-800">Nueva Categoría de Mantenimiento</h2>
                </div>
                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>
        </template>

        <Card>
            <CardHeader>
                <CardTitle>Información de la Categoría</CardTitle>
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
                                placeholder="Ej: Plomería, Electricidad"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <div v-if="form.errors.name" class="text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Color -->
                        <div class="space-y-2">
                            <Label for="color">Color *</Label>
                            <Select v-model="form.color">
                                <SelectTrigger>
                                    <div class="flex items-center space-x-2">
                                        <div class="h-4 w-4 rounded-full border" :style="{ backgroundColor: form.color }"></div>
                                        <span>{{ colorOptions.find((c) => c.value === form.color)?.label || 'Seleccionar color' }}</span>
                                    </div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
                                        <div class="flex items-center space-x-2">
                                            <div class="h-4 w-4 rounded-full border" :style="{ backgroundColor: color.value }"></div>
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
                            placeholder="Describa brevemente el tipo de trabajo que abarca esta categoría"
                            rows="3"
                            :class="{ 'border-red-500': form.errors.description }"
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
                                step="0.5"
                                min="0"
                                placeholder="2.5"
                                :class="{ 'border-red-500': form.errors.estimated_hours }"
                            />
                            <div class="text-sm text-gray-500">Tiempo promedio estimado para completar trabajos de esta categoría</div>
                            <div v-if="form.errors.estimated_hours" class="text-sm text-red-600">
                                {{ form.errors.estimated_hours }}
                            </div>
                        </div>
                    </div>

                    <!-- Requiere Aprobación -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox id="requires_approval" v-model:checked="form.requires_approval" />
                            <Label for="requires_approval" class="text-sm leading-none font-medium">
                                Requiere aprobación del concejo de administración
                            </Label>
                        </div>
                        <div class="text-sm text-gray-600">
                            Si se marca esta opción, todas las solicitudes de esta categoría requerirán aprobación del concejo antes de poder ser
                            asignadas al personal.
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-4">
                        <Button type="button" variant="outline" @click="goBack"> Cancelar </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Crear Categoría' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template>
