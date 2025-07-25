<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building, Calendar, Download, Edit, Eye, FileText, Home, IdCard, Mail, Phone, User, UserCheck } from 'lucide-vue-next';

interface Apartment {
    id: number;
    number: string;
    tower: string;
    floor: number;
    apartment_type: {
        name: string;
        area_sqm: number;
        bedrooms: number;
        bathrooms: number;
    };
}

interface Resident {
    id: number;
    document_type: string;
    document_number: string;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    mobile_phone?: string;
    birth_date?: string;
    gender?: 'M' | 'F' | 'Other';
    emergency_contact?: string;
    apartment?: Apartment;
    resident_type: 'Owner' | 'Tenant' | 'Family';
    status: 'Active' | 'Inactive';
    start_date: string;
    end_date?: string;
    notes?: string;
    documents?: any[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    resident: Resident;
}>();

const formatDate = (date: string | null) => {
    if (!date) return null;
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateTime: string | null) => {
    if (!dateTime) return null;
    return new Date(dateTime).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getGenderLabel = (gender: string) => {
    const labels = {
        M: 'Masculino',
        F: 'Femenino',
        Other: 'Otro',
    };
    return labels[gender] || gender;
};

const getResidentTypeLabel = (type: string) => {
    const labels = {
        Owner: 'Propietario',
        Tenant: 'Arrendatario',
        Family: 'Familiar',
    };
    return labels[type] || type;
};

const getResidentTypeColor = (type: string) => {
    const colors = {
        Owner: 'bg-green-100 text-green-800',
        Tenant: 'bg-blue-100 text-blue-800',
        Family: 'bg-yellow-100 text-yellow-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getStatusColor = (status: string) => {
    return status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Escritorio', href: '/dashboard' },
    { title: 'Residentes', href: '/residents' },
    { title: `${props.resident.first_name} ${props.resident.last_name}`, href: `/residents/${props.resident.id}` },
];
</script>

<template>
    <Head :title="`${resident.first_name} ${resident.last_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">{{ resident.first_name }} {{ resident.last_name }}</h1>
                    <div class="flex items-center gap-3">
                        <Badge :class="getStatusColor(resident.status)">
                            {{ resident.status === 'Active' ? 'Activo' : 'Inactivo' }}
                        </Badge>
                        <Badge :class="getResidentTypeColor(resident.resident_type)">
                            {{ getResidentTypeLabel(resident.resident_type) }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/residents">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link :href="`/residents/${resident.id}/edit`">
                        <Button class="gap-2">
                            <Edit class="h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Información Personal -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Información Personal
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex items-center gap-3">
                                <IdCard class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Documento</p>
                                    <p class="text-base">{{ resident.document_type }} {{ resident.document_number }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Mail class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Email</p>
                                    <p class="text-base">{{ resident.email }}</p>
                                </div>
                            </div>

                            <div v-if="resident.phone" class="flex items-center gap-3">
                                <Phone class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Teléfono</p>
                                    <p class="text-base">{{ resident.phone }}</p>
                                </div>
                            </div>

                            <div v-if="resident.mobile_phone" class="flex items-center gap-3">
                                <Phone class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Celular</p>
                                    <p class="text-base">{{ resident.mobile_phone }}</p>
                                </div>
                            </div>

                            <div v-if="resident.birth_date" class="flex items-center gap-3">
                                <Calendar class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Fecha de Nacimiento</p>
                                    <p class="text-base">{{ formatDate(resident.birth_date) }}</p>
                                </div>
                            </div>

                            <div v-if="resident.gender" class="flex items-center gap-3">
                                <User class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Género</p>
                                    <p class="text-base">{{ getGenderLabel(resident.gender) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="resident.emergency_contact">
                            <Separator class="my-4" />
                            <div class="flex items-start gap-3">
                                <UserCheck class="mt-1 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Contacto de Emergencia</p>
                                    <p class="text-base whitespace-pre-line">{{ resident.emergency_contact }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de Residencia -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Home class="h-5 w-5" />
                            Información de Residencia
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div v-if="resident.apartment" class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Building class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Ubicación</p>
                                    <p class="text-base font-medium">
                                        Torre {{ resident.apartment.tower }} - Apartamento {{ resident.apartment.number }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Piso {{ resident.apartment.floor }} - {{ resident.apartment.apartment_type.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 rounded-lg bg-muted/30 p-4">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Área</p>
                                    <p class="text-base">{{ resident.apartment.apartment_type.area_sqm }}m²</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Habitaciones</p>
                                    <p class="text-base">{{ resident.apartment.apartment_type.bedrooms }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Baños</p>
                                    <p class="text-base">{{ resident.apartment.apartment_type.bathrooms }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tipo</p>
                                    <p class="text-base">{{ resident.apartment.apartment_type.name }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center">
                            <Home class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p class="text-muted-foreground">Sin apartamento asignado</p>
                        </div>

                        <Separator />

                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Calendar class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                                    <p class="text-base">{{ formatDate(resident.start_date) }}</p>
                                </div>
                            </div>

                            <div v-if="resident.end_date" class="flex items-center gap-3">
                                <Calendar class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Fecha de Fin</p>
                                    <p class="text-base">{{ formatDate(resident.end_date) }}</p>
                                </div>
                            </div>

                            <div v-if="resident.notes">
                                <div class="flex items-start gap-3">
                                    <FileText class="mt-1 h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Notas</p>
                                        <p class="text-base whitespace-pre-line">{{ resident.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Documentos Adjuntos -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Documentos Adjuntos
                    </CardTitle>
                    <CardDescription> Archivos y documentos relacionados con el residente </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="resident.documents && resident.documents.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="document in resident.documents"
                            :key="document.id"
                            class="rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <FileText class="h-8 w-8 text-blue-500" />
                                    <div>
                                        <p class="text-sm font-medium">{{ document.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDateTime(document.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button variant="outline" size="sm">
                                        <Download class="h-4 w-4" />
                                    </Button>
                                    <Button variant="outline" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center">
                        <FileText class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                        <h3 class="mb-2 text-lg font-medium">No hay documentos</h3>
                        <p class="text-muted-foreground">No se han adjuntado documentos para este residente</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Información del Sistema -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="text-lg">Información del Sistema</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Fecha de Creación</p>
                            <p class="text-base">{{ formatDateTime(resident.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Última Actualización</p>
                            <p class="text-base">{{ formatDateTime(resident.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
