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

interface VoteData {
    id: number;
    title: string;
    description: string;
    type: string;
    required_percentage: number;
    status: string;
}

interface FormData {
    title: string;
    description: string;
    type: string;
    required_percentage: number;
}

const props = defineProps<{
    assembly: Assembly;
    vote: VoteData;
}>();

// Form state
const form = useForm<FormData>({
    title: props.vote.title,
    description: props.vote.description,
    type: props.vote.type,
    required_percentage: props.vote.required_percentage,
});

const submit = () => {
    form.put(route('assemblies.votes.update', [props.assembly.id, props.vote.id]));
};

const canEdit = props.vote.status === 'draft';

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
    if (canEdit) {
        const voteType = voteTypes.find(vt => vt.value === type);
        if (voteType) {
            form.required_percentage = voteType.percentage;
        }
    }
};

// Status badge
const getStatusBadge = (status: string) => {
    const statusMap = {
        draft: { text: 'Borrador', class: 'bg-gray-100 text-gray-800' },
        active: { text: 'Activa', class: 'bg-green-100 text-green-800' },
        closed: { text: 'Cerrada', class: 'bg-blue-100 text-blue-800' },
    };
    return statusMap[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Asambleas', href: '/assemblies' },
    { title: props.assembly.title, href: `/assemblies/${props.assembly.id}` },
    { title: 'Votaciones', href: `/assemblies/${props.assembly.id}/votes` },
    { title: props.vote.title, href: `/assemblies/${props.assembly.id}/votes/${props.vote.id}` },
    { title: 'Editar', href: `/assemblies/${props.assembly.id}/votes/${props.vote.id}/edit` },
];
</script>

<template>
    <Head title="Editar Votación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Editar Votación</h1>
                        <Badge :class="getStatusBadge(vote.status).class">
                            {{ getStatusBadge(vote.status).text }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">{{ assembly.title }}</p>
                </div>
                <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}`">
                    <Button variant="outline" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Button>
                </Link>
            </div>

            <!-- Warning for non-editable status -->
            <Card v-if="!canEdit" class="mb-8 border-amber-200 bg-amber-50">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-amber-100 p-2">
                            <Vote class="h-5 w-5 text-amber-600" />
                        </div>
                        <div>
                            <h3 class="font-medium text-amber-800">Edición no disponible</h3>
                            <p class="text-sm text-amber-600">
                                Esta votación está en estado "{{ getStatusBadge(vote.status).text }}" y no puede ser editada.
                                Solo las votaciones en estado "Borrador" pueden modificarse.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

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
                            Edita los detalles principales de la votación
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
                                placeholder="Describe el tema a votar y proporciona contexto relevante..."
                                :class="{ 'border-red-500': form.errors.description }"
                                :disabled="!canEdit"
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
                            Modifica el tipo de votación y los requisitos de aprobación
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Vote Type -->
                        <div class="space-y-3">
                            <Label for="type">Tipo de Votación *</Label>
                            <Select 
                                v-model="form.type" 
                                @update:model-value="updatePercentage"
                                :disabled="!canEdit"
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
                                :disabled="!canEdit || form.type === 'unanimous'"
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
                            Vista previa de cómo aparecerá la votación actualizada
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
                    <Link :href="`/assemblies/${assembly.id}/votes/${vote.id}`">
                        <Button variant="outline">Cancelar</Button>
                    </Link>
                    <Button 
                        type="submit" 
                        :disabled="form.processing || !canEdit" 
                        class="gap-2"
                    >
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : 'Actualizar Votación' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>