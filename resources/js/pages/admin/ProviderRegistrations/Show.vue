<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Calendar, Mail, Phone, User } from 'lucide-vue-next';

interface ProviderRegistration {
    id: number;
    company_name: string;
    contact_name: string;
    email: string;
    phone: string;
    service_type: string;
    description: string | null;
    status: 'pending' | 'approved' | 'rejected';
    admin_notes: string | null;
    reviewed_at: string | null;
    reviewed_by: { id: number; name: string } | null;
    created_at: string;
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
];

const getStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        approved: { text: 'Aprobado', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazado', class: 'bg-red-100 text-red-800' },
    };
    return badges[status] || badges.pending;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Solicitud de ${registration.company_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="router.visit('/admin/provider-registrations')">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">{{ registration.company_name }}</h1>
                        <p class="text-sm text-muted-foreground">Solicitud #{{ registration.id }}</p>
                    </div>
                </div>
                <Badge :class="getStatusBadge(registration.status).class" class="text-base">
                    {{ getStatusBadge(registration.status).text }}
                </Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Información de la Empresa -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información de la Empresa</CardTitle>
                        <CardDescription>Datos generales del proveedor</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Building2 class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Nombre de la Empresa</p>
                                <p class="text-base">{{ registration.company_name }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <User class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Persona de Contacto</p>
                                <p class="text-base">{{ registration.contact_name }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <Mail class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Email</p>
                                <p class="text-base">{{ registration.email }}</p>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex items-start gap-3">
                            <Phone class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Teléfono</p>
                                <p class="text-base">{{ registration.phone }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información del Servicio -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Servicio</CardTitle>
                        <CardDescription>Tipo de servicio y descripción</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Tipo de Servicio</p>
                            <p class="text-base">{{ registration.service_type }}</p>
                        </div>

                        <Separator />

                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Descripción</p>
                            <p v-if="registration.description" class="text-base whitespace-pre-wrap">
                                {{ registration.description }}
                            </p>
                            <p v-else class="text-base text-muted-foreground italic">Sin descripción</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de Revisión -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Información de Revisión</CardTitle>
                        <CardDescription>Detalles sobre el estado y revisión de la solicitud</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-3">
                        <div class="flex items-start gap-3">
                            <Calendar class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de Solicitud</p>
                                <p class="text-base">{{ formatDate(registration.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="registration.reviewed_at" class="flex items-start gap-3">
                            <Calendar class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de Revisión</p>
                                <p class="text-base">{{ formatDate(registration.reviewed_at) }}</p>
                            </div>
                        </div>

                        <div v-if="registration.reviewed_by" class="flex items-start gap-3">
                            <User class="mt-1 h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Revisado por</p>
                                <p class="text-base">{{ registration.reviewed_by.name }}</p>
                            </div>
                        </div>

                        <div v-if="registration.admin_notes" class="md:col-span-3">
                            <Separator class="mb-4" />
                            <p class="text-sm font-medium text-muted-foreground">Notas del Administrador</p>
                            <p class="mt-2 text-base whitespace-pre-wrap">{{ registration.admin_notes }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Actions -->
            <div v-if="registration.status === 'pending'" class="flex justify-end gap-2">
                <Button
                    variant="outline"
                    @click="router.visit(`/admin/provider-registrations/${registration.id}/edit`)"
                >
                    Editar
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
