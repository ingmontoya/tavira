<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface ProviderCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
}

interface ProviderRegistration {
    id: number;
    company_name: string;
    contact_name: string;
    email: string;
    phone: string;
    service_type: string;
    description: string | null;
    status: 'pending' | 'approved' | 'rejected';
    categories: ProviderCategory[];
}

const props = defineProps<{
    registration: ProviderRegistration;
    categories: ProviderCategory[];
}>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Solicitudes de Proveedores',
        href: '/admin/provider-registrations',
    },
    {
        title: props.registration.company_name,
        href: `/admin/provider-registrations/${props.registration.id}`,
    },
    {
        title: 'Editar',
        href: `/admin/provider-registrations/${props.registration.id}/edit`,
    },
];

const form = useForm({
    company_name: props.registration.company_name,
    contact_name: props.registration.contact_name,
    email: props.registration.email,
    phone: props.registration.phone,
    service_type: props.registration.service_type,
    description: props.registration.description || '',
    category_ids: props.registration.categories?.map((c) => c.id) || [],
});

const toggleCategory = (categoryId: number) => {
    const index = form.category_ids.indexOf(categoryId);
    if (index > -1) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(categoryId);
    }
};

const isCategorySelected = (categoryId: number) => {
    return form.category_ids.includes(categoryId);
};

const submit = () => {
    form.put(`/admin/provider-registrations/${props.registration.id}`, {
        onSuccess: () => {
            router.visit(`/admin/provider-registrations/${props.registration.id}`);
        },
    });
};
</script>

<template>
    <Head :title="`Editar ${registration.company_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="sm" @click="router.visit(`/admin/provider-registrations/${registration.id}`)">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Editar Solicitud</h1>
                    <p class="text-sm text-muted-foreground">{{ registration.company_name }}</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Información de la Empresa -->
                    <Card class="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Información de la Empresa</CardTitle>
                            <CardDescription>Actualiza los datos de la empresa</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="company_name">Nombre de la Empresa *</Label>
                                <Input id="company_name" v-model="form.company_name" type="text" required placeholder="Ej: Constructora ABC" />
                                <p v-if="form.errors.company_name" class="text-sm text-red-600">
                                    {{ form.errors.company_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="contact_name">Persona de Contacto *</Label>
                                <Input id="contact_name" v-model="form.contact_name" type="text" required placeholder="Ej: Juan Pérez" />
                                <p v-if="form.errors.contact_name" class="text-sm text-red-600">
                                    {{ form.errors.contact_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email *</Label>
                                <Input id="email" v-model="form.email" type="email" required placeholder="contacto@empresa.com" />
                                <p v-if="form.errors.email" class="text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="phone">Teléfono *</Label>
                                <Input id="phone" v-model="form.phone" type="tel" required placeholder="Ej: 3001234567" />
                                <p v-if="form.errors.phone" class="text-sm text-red-600">
                                    {{ form.errors.phone }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información del Servicio -->
                    <Card class="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Información del Servicio</CardTitle>
                            <CardDescription>Actualiza las categorías y descripción de servicios</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div>
                                    <Label>Categorías de Servicio *</Label>
                                    <p class="text-sm text-muted-foreground">Selecciona todas las categorías que apliquen</p>
                                </div>
                                <div class="grid max-h-64 grid-cols-1 gap-3 overflow-y-auto rounded-lg border bg-muted/30 p-4">
                                    <div
                                        v-for="category in categories"
                                        :key="category.id"
                                        class="flex items-start space-x-3 rounded p-2 transition-colors hover:bg-muted/50"
                                    >
                                        <input
                                            :id="`category-${category.id}`"
                                            type="checkbox"
                                            :value="category.id"
                                            :checked="isCategorySelected(category.id)"
                                            class="mt-1 h-4 w-4 cursor-pointer rounded border-gray-300 text-primary focus:ring-primary"
                                            @change="toggleCategory(category.id)"
                                        />
                                        <div class="flex-1">
                                            <label :for="`category-${category.id}`" class="cursor-pointer font-medium">
                                                {{ category.name }}
                                            </label>
                                            <p v-if="category.description" class="mt-0.5 text-xs text-muted-foreground">
                                                {{ category.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.category_ids" class="text-sm text-red-600">
                                    {{ form.errors.category_ids }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="service_type">Tipo de Servicio (Opcional)</Label>
                                <Input
                                    id="service_type"
                                    v-model="form.service_type"
                                    type="text"
                                    placeholder="Ej: Plomería especializada, Electricidad industrial"
                                />
                                <p class="text-xs text-muted-foreground">Campo adicional para especificar el tipo de servicio</p>
                                <p v-if="form.errors.service_type" class="text-sm text-red-600">
                                    {{ form.errors.service_type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Descripción del Servicio</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe brevemente los servicios que ofreces..."
                                    rows="5"
                                />
                                <p v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="router.visit(`/admin/provider-registrations/${registration.id}`)">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
