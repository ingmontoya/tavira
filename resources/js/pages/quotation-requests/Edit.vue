<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface ProviderCategory {
    id: number;
    name: string;
    description: string | null;
}

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    requirements: string | null;
    categories: ProviderCategory[];
}

const props = defineProps<{
    quotationRequest: QuotationRequest;
    categories: ProviderCategory[];
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Solicitudes de Cotización',
        href: '/quotation-requests',
    },
    {
        title: 'Editar Solicitud',
        href: `/quotation-requests/${props.quotationRequest.id}/edit`,
    },
];

const form = useForm({
    title: props.quotationRequest.title,
    description: props.quotationRequest.description,
    deadline: props.quotationRequest.deadline || '',
    requirements: props.quotationRequest.requirements || '',
    category_ids: props.quotationRequest.categories.map((c) => c.id),
});

const toggleCategory = (categoryId: number) => {
    const index = form.category_ids.indexOf(categoryId);
    if (index > -1) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(categoryId);
    }
};

const submit = () => {
    form.put(`/quotation-requests/${props.quotationRequest.id}`, {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>

<template>
    <Head title="Editar Solicitud de Cotización" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Editar Solicitud de Cotización</h1>
                    <p class="text-sm text-muted-foreground">Actualiza los detalles de la solicitud</p>
                </div>
                <Button variant="outline" @click="router.visit(`/quotation-requests/${quotationRequest.id}`)">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-4">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>Detalles principales de la solicitud</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="title">Título *</Label>
                                <Input id="title" v-model="form.title" placeholder="Ej: Mantenimiento de ascensores" required />
                                <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Descripción *</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe en detalle lo que necesitas..."
                                    rows="5"
                                    required
                                />
                                <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="deadline">Fecha Límite</Label>
                                <Input id="deadline" v-model="form.deadline" type="date" />
                                <p v-if="form.errors.deadline" class="text-sm text-red-600">{{ form.errors.deadline }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="requirements">Requisitos Específicos</Label>
                                <Textarea
                                    id="requirements"
                                    v-model="form.requirements"
                                    placeholder="Requisitos adicionales, documentación necesaria, etc."
                                    rows="3"
                                />
                                <p v-if="form.errors.requirements" class="text-sm text-red-600">{{ form.errors.requirements }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Categorías de Proveedores *</CardTitle>
                            <CardDescription>Selecciona las categorías de proveedores que deben recibir esta solicitud</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div v-for="category in categories" :key="category.id" class="flex items-start space-x-3 rounded-lg border p-3">
                                    <Checkbox
                                        :id="`category-${category.id}`"
                                        :checked="form.category_ids.includes(category.id)"
                                        @update:checked="toggleCategory(category.id)"
                                    />
                                    <div class="flex-1">
                                        <Label :for="`category-${category.id}`" class="cursor-pointer font-medium">
                                            {{ category.name }}
                                        </Label>
                                        <p v-if="category.description" class="text-sm text-muted-foreground">
                                            {{ category.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.category_ids" class="mt-2 text-sm text-red-600">{{ form.errors.category_ids }}</p>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-2">
                        <Button type="button" variant="outline" @click="router.visit(`/quotation-requests/${quotationRequest.id}`)">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
