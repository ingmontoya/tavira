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
import { ArrowLeft, Save, Vote } from 'lucide-vue-next';

interface Assembly {
    id: number;
    title: string;
    status: string;
}

interface FormData {
    title: string;
    description: string;
    type: string;
    required_percentage: number;
    options: string[];
}

const props = defineProps<{
    assembly: Assembly;
}>();

// Form state
const form = useForm<FormData>({
    title: '',
    description: '',
    type: 'simple',
    required_percentage: 50,
    options: ['Sí', 'No', 'Abstención'],
});

const submit = () => {
    form.post(route('assemblies.votes.store', props.assembly.id));
};

// Vote types
const voteTypes = [
    { 
        value: 'simple', 
        label: 'Mayoría Simple', 
        description: 'Más del 50% de votos a favor',
        percentage: 50
    },
    { 
        value: 'qualified', 
        label: 'Mayoría Calificada', 
        description: 'Requiere 2/3 o más de los votos',
        percentage: 67
    },
    { 
        value: 'unanimous', 
        label: 'Unanimidad', 
        description: 'Requiere 100% de votos a favor',
        percentage: 100
    },
];

// Update percentage when type changes
const updatePercentage = (type: string) => {
    const voteType = voteTypes.find(vt => vt.value === type);
    if (voteType) {
        form.required_percentage = voteType.percentage;
    }
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Votaciones', href: `/assemblies/${props.assembly.id}/votes` },
    { title: 'Nueva Votación', href: `/assemblies/${props.assembly.id}/votes/create` },
];
</script>

<template>
    <Head title="Nueva Votación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nueva Votación</h1>
                    <p class="text-muted-foreground">{{ assembly.title }}</p>
                </div>
                <Link :href="`/assemblies/${assembly.id}/votes`">
                    <Button variant="outline" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Button>
                </Link>
            </div>

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Vote class="h-5 w-5" />
                            Información Básica
                        </CardTitle>
                        <CardDescription>
                            Define los detalles principales de la votación
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Title -->
                        <div class="space-y-2">
                            <Label for="title">Título de la Votación *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Ej: Aprobación del presupuesto anual 2024"
                                :class="{ 'border-red-500': form.errors.title }"
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
                                placeholder="Describe el tema a votar y proporciona contexto relevante..."
                                :class="{ 'border-red-500': form.errors.description }"
                                rows="4"
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Vote Configuration -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración de Votación</CardTitle>
                        <CardDescription>
                            Define el tipo de votación y los requisitos de aprobación
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Vote Type -->
                        <div class="space-y-3">
                            <Label for="type">Tipo de Votación *</Label>
                            <Select 
                                v-model="form.type" 
                                @update:model-value="updatePercentage"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona el tipo de votación" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="type in voteTypes" 
                                        :key="type.value" 
                                        :value="type.value"
                                    >
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ type.label }}</span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ type.description }}
                                            </span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-sm text-red-600">
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <!-- Required Percentage -->
                        <div class="space-y-2">
                            <Label for="required_percentage">Porcentaje Requerido para Aprobación (%)</Label>
                            <Input
                                id="required_percentage"
                                v-model.number="form.required_percentage"
                                type="number"
                                min="1"
                                max="100"
                                :class="{ 'border-red-500': form.errors.required_percentage }"
                                :disabled="form.type === 'unanimous'"
                            />
                            <p class="text-xs text-muted-foreground">
                                Porcentaje de votos afirmativos necesarios para aprobar la propuesta
                            </p>
                            <p v-if="form.errors.required_percentage" class="text-sm text-red-600">
                                {{ form.errors.required_percentage }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Vote Options Preview -->
                <Card>
                    <CardHeader>
                        <CardTitle>Opciones de Votación</CardTitle>
                        <CardDescription>
                            Las opciones que estarán disponibles para los residentes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="rounded-lg border p-4 text-center bg-green-50 border-green-200">
                                    <div class="text-lg font-semibold text-green-800">A Favor</div>
                                    <div class="text-sm text-green-600">Voto positivo</div>
                                </div>
                                <div class="rounded-lg border p-4 text-center bg-red-50 border-red-200">
                                    <div class="text-lg font-semibold text-red-800">En Contra</div>
                                    <div class="text-sm text-red-600">Voto negativo</div>
                                </div>
                                <div class="rounded-lg border p-4 text-center bg-gray-50 border-gray-200">
                                    <div class="text-lg font-semibold text-gray-800">Abstención</div>
                                    <div class="text-sm text-gray-600">No participa</div>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground text-center">
                                Los residentes podrán elegir entre estas tres opciones durante la votación
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Vote Summary -->
                <Card v-if="form.title">
                    <CardHeader>
                        <CardTitle>Resumen de la Votación</CardTitle>
                        <CardDescription>
                            Vista previa de cómo aparecerá la votación
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-lg border bg-muted/50 p-4">
                            <h3 class="font-semibold">{{ form.title }}</h3>
                            <p v-if="form.description" class="text-sm text-muted-foreground mt-1">
                                {{ form.description }}
                            </p>
                            <div class="mt-3 flex items-center gap-4 text-xs">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-blue-800">
                                    {{ voteTypes.find(t => t.value === form.type)?.label }}
                                </span>
                                <span class="text-muted-foreground">
                                    Requiere {{ form.required_percentage }}% para aprobar
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Link :href="`/assemblies/${assembly.id}/votes`">
                        <Button variant="outline">Cancelar</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="gap-2">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : 'Crear Votación' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>