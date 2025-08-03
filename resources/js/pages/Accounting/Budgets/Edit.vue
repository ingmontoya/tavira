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
import { ArrowLeft, Save, Trash2, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Budget {
    id: number;
    name: string;
    description?: string;
    year: number;
    start_date: string;
    end_date: string;
    status: string;
}

interface FormData {
    name: string;
    description: string;
    year: number;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    budget: Budget;
}>();

// Form state
const form = useForm<FormData>({
    name: props.budget.name,
    description: props.budget.description || '',
    year: props.budget.year,
    start_date: props.budget.start_date,
    end_date: props.budget.end_date,
});

// UI state
const isUnsavedChanges = ref(false);
const canEdit = computed(() => props.budget.status === 'Draft');

// Computed properties
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = currentYear - 2; i <= currentYear + 5; i++) {
        years.push(i);
    }
    return years;
});

// Methods
const submit = () => {
    form.put(route('accounting.budgets.update', props.budget.id), {
        onSuccess: () => {
            isUnsavedChanges.value = false;
        },
    });
};

const deleteBudget = () => {
    if (confirm('¿Estás seguro de que deseas eliminar este presupuesto? Esta acción no se puede deshacer.')) {
        form.delete(route('accounting.budgets.destroy', props.budget.id));
    }
};

const resetForm = () => {
    form.reset();
    isUnsavedChanges.value = false;
};

// Watch for form changes
watch(
    form,
    () => {
        isUnsavedChanges.value = true;
    },
    { deep: true },
);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Presupuestos', href: '/accounting/budgets' },
    { title: `Editar: ${props.budget.name}`, href: `/accounting/budgets/${props.budget.id}/edit` },
];
</script>

<template>
    <Head :title="`Editar: ${budget.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Editar Presupuesto</h1>
                    <p class="text-muted-foreground">{{ budget.name }} - {{ budget.status }}</p>
                    <div v-if="!canEdit" class="inline-block rounded bg-orange-50 px-2 py-1 text-sm text-orange-600">
                        ⚠️ Solo se pueden editar presupuestos en estado "Borrador"
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="`/accounting/budgets/${budget.id}`">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Validation Errors -->
            <ValidationErrors :errors="form.errors" />

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Wallet class="h-5 w-5" />
                            Información del Presupuesto
                        </CardTitle>
                        <CardDescription> Datos básicos del presupuesto </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Name and Year -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Nombre del Presupuesto *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Ej: Presupuesto 2024"
                                    :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="year">Año *</Label>
                                <Select v-model="form.year" :disabled="!canEdit">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el año" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.year" class="text-sm text-red-600">
                                    {{ form.errors.year }}
                                </p>
                            </div>
                        </div>

                        <!-- Period -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start_date">Fecha de Inicio *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.start_date }"
                                />
                                <p v-if="form.errors.start_date" class="text-sm text-red-600">
                                    {{ form.errors.start_date }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="end_date">Fecha de Fin *</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    :disabled="!canEdit"
                                    :class="{ 'border-red-500': form.errors.end_date }"
                                />
                                <p v-if="form.errors.end_date" class="text-sm text-red-600">
                                    {{ form.errors.end_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Descripción</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Descripción del presupuesto y objetivos..."
                                :disabled="!canEdit"
                                :class="{ 'border-red-500': form.errors.description }"
                                rows="3"
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex items-center justify-between">
                    <Button type="button" variant="destructive" :disabled="!canEdit" @click="deleteBudget" class="gap-2">
                        <Trash2 class="h-4 w-4" />
                        Eliminar Presupuesto
                    </Button>

                    <div class="flex items-center gap-3">
                        <Button type="button" variant="outline" @click="resetForm" :disabled="!canEdit"> Descartar Cambios </Button>

                        <Button type="submit" :disabled="form.processing || !canEdit" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
