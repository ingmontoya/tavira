<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface ProviderRegistration {
    id: number;
    company_name: string;
    contact_name: string;
    email: string;
    phone: string;
    service_type: string;
    description: string | null;
    status: 'pending' | 'approved' | 'rejected';
}

const props = defineProps<{
    registration: ProviderRegistration;
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
});

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
                                <Input
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    required
                                    placeholder="Ej: Constructora ABC"
                                />
                                <p v-if="form.errors.company_name" class="text-sm text-red-600">
                                    {{ form.errors.company_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="contact_name">Persona de Contacto *</Label>
                                <Input
                                    id="contact_name"
                                    v-model="form.contact_name"
                                    type="text"
                                    required
                                    placeholder="Ej: Juan Pérez"
                                />
                                <p v-if="form.errors.contact_name" class="text-sm text-red-600">
                                    {{ form.errors.contact_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="contacto@empresa.com"
                                />
                                <p v-if="form.errors.email" class="text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="phone">Teléfono *</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="Ej: 3001234567"
                                />
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
                            <CardDescription>Actualiza el tipo de servicio y descripción</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="service_type">Tipo de Servicio *</Label>
                                <Input
                                    id="service_type"
                                    v-model="form.service_type"
                                    type="text"
                                    required
                                    placeholder="Ej: Plomería, Electricidad, Construcción"
                                />
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
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(`/admin/provider-registrations/${registration.id}`)"
                    >
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
