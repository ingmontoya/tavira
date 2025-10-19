<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, UserCog } from 'lucide-vue-next';

export interface MaintenanceStaff {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    specialties: string[];
    hourly_rate: number | null;
    is_internal: boolean;
    is_active: boolean;
    availability_schedule: Record<string, any> | null;
}

interface Props {
    staff: MaintenanceStaff;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.staff.name,
    phone: props.staff.phone || '',
    email: props.staff.email || '',
    specialties: props.staff.specialties ? props.staff.specialties.join(', ') : '',
    hourly_rate: props.staff.hourly_rate?.toString() || '',
    is_internal: props.staff.is_internal,
    is_active: props.staff.is_active,
});

const submit = () => {
    // Convert specialties string to array
    const submissionData = {
        ...form.data(),
        specialties: form.specialties
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s.length > 0),
    };

    form.transform(() => submissionData).put(route('maintenance-staff.update', props.staff.id));
};

const goBack = () => {
    router.visit(route('maintenance-staff.show', props.staff.id));
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
        title: 'Personal',
        href: '/maintenance-staff',
    },
    {
        title: props.staff.name,
        href: `/maintenance-staff/${props.staff.id}`,
    },
    {
        title: 'Editar',
        href: `/maintenance-staff/${props.staff.id}/edit`,
    },
];
</script>

<template>
    <Head title="Editar Personal de Mantenimiento" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header with title and action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <UserCog class="h-8 w-8 text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Editar Personal</h1>
                        <p class="text-sm text-gray-600">{{ staff.name }}</p>
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
                        <UserCog class="h-5 w-5" />
                        <span>Información del Personal</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Información Personal -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Información Personal</h3>

                            <div class="space-y-2">
                                <Label for="name">Nombre Completo *</Label>
                                <Input id="name" v-model="form.name" type="text" placeholder="Ej: Juan Pérez García" required />
                                <div v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="phone">Teléfono</Label>
                                    <Input id="phone" v-model="form.phone" type="tel" placeholder="Ej: + +44 7447 313219" />
                                    <div v-if="form.errors.phone" class="text-sm text-red-600">
                                        {{ form.errors.phone }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="email">Correo Electrónico</Label>
                                    <Input id="email" v-model="form.email" type="email" placeholder="ejemplo@correo.com" />
                                    <div v-if="form.errors.email" class="text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Profesional -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Información Profesional</h3>

                            <div class="space-y-2">
                                <Label for="specialties">Especialidades</Label>
                                <Textarea
                                    id="specialties"
                                    v-model="form.specialties"
                                    placeholder="Ej: Plomería, Electricidad, Pintura (separar por comas)"
                                    rows="3"
                                />
                                <p class="text-sm text-gray-500">Separe las especialidades con comas</p>
                                <div v-if="form.errors.specialties" class="text-sm text-red-600">
                                    {{ form.errors.specialties }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="hourly_rate">Tarifa por Hora (COP)</Label>
                                <Input id="hourly_rate" v-model="form.hourly_rate" type="number" min="0" step="0.01" placeholder="Ej: 50000" />
                                <div v-if="form.errors.hourly_rate" class="text-sm text-red-600">
                                    {{ form.errors.hourly_rate }}
                                </div>
                            </div>
                        </div>

                        <!-- Configuraciones -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium">Configuraciones</h3>

                            <div class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_internal" v-model:checked="form.is_internal" />
                                    <Label for="is_internal" class="text-sm"> Personal interno </Label>
                                </div>
                                <p class="ml-6 text-sm text-gray-500">Marcar si es empleado directo del conjunto (no contratista externo)</p>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_active" v-model:checked="form.is_active" />
                                    <Label for="is_active" class="text-sm"> Personal activo </Label>
                                </div>
                                <p class="ml-6 text-sm text-gray-500">Solo el personal activo puede recibir asignaciones de trabajo</p>
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
