<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    Mail,
    Phone,
    MapPin,
    FileText,
    User,
    Edit,
    Power,
    Trash2
} from 'lucide-vue-next';
import { ref } from 'vue';

interface ProviderCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

interface Provider {
    id: number;
    name: string;
    document_type: string | null;
    document_number: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    tax_regime: string | null;
    notes: string | null;
    is_active: boolean;
    categories: ProviderCategory[];
    created_at: string;
    updated_at: string;
}

interface Props {
    provider: Provider;
}

const props = defineProps<Props>();

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Proveedores',
        href: '/admin/providers',
    },
    {
        title: props.provider.name,
        href: `/admin/providers/${props.provider.id}`,
    },
];

const showDeleteDialog = ref(false);
const showToggleDialog = ref(false);

const deleteForm = useForm({});
const toggleForm = useForm({});

const handleToggleStatus = () => {
    toggleForm.post(`/admin/providers/${props.provider.id}/toggle-status`, {
        onSuccess: () => {
            showToggleDialog.value = false;
        },
    });
};

const handleDelete = () => {
    deleteForm.delete(`/admin/providers/${props.provider.id}`, {
        onSuccess: () => {
            router.visit('/admin/providers');
        },
    });
};

const getStatusBadge = () => {
    if (props.provider.is_active) {
        return { variant: 'success' as const, label: 'Activo' };
    }
    return { variant: 'secondary' as const, label: 'Inactivo' };
};
</script>

<template>
    <Head :title="provider.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="router.visit('/admin/providers')">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold">{{ provider.name }}</h1>
                            <Badge :variant="getStatusBadge().variant">
                                {{ getStatusBadge().label }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">Información del proveedor</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showToggleDialog = true"
                    >
                        <Power class="mr-2 h-4 w-4" />
                        {{ provider.is_active ? 'Desactivar' : 'Activar' }}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="router.visit(`/admin/providers/${provider.id}/edit`)"
                    >
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Información de la Empresa -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Información de la Empresa
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Nombre</label>
                            <p class="text-sm">{{ provider.name }}</p>
                        </div>

                        <div v-if="provider.document_type || provider.document_number" class="grid gap-4 md:grid-cols-2">
                            <div v-if="provider.document_type">
                                <label class="text-sm font-medium text-muted-foreground">Tipo de Documento</label>
                                <p class="text-sm">{{ provider.document_type }}</p>
                            </div>
                            <div v-if="provider.document_number">
                                <label class="text-sm font-medium text-muted-foreground">Número de Documento</label>
                                <p class="text-sm">{{ provider.document_number }}</p>
                            </div>
                        </div>

                        <div v-if="provider.tax_regime">
                            <label class="text-sm font-medium text-muted-foreground">Régimen Tributario</label>
                            <p class="text-sm">{{ provider.tax_regime }}</p>
                        </div>

                        <div v-if="provider.email" class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Email</label>
                                <p class="text-sm">{{ provider.email }}</p>
                            </div>
                        </div>

                        <div v-if="provider.phone" class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Teléfono</label>
                                <p class="text-sm">{{ provider.phone }}</p>
                            </div>
                        </div>

                        <div v-if="provider.address || provider.city" class="flex items-start gap-2">
                            <MapPin class="h-4 w-4 text-muted-foreground mt-1" />
                            <div class="flex-1">
                                <label class="text-sm font-medium text-muted-foreground">Dirección</label>
                                <p v-if="provider.address" class="text-sm">{{ provider.address }}</p>
                                <p v-if="provider.city || provider.country" class="text-sm text-muted-foreground">
                                    {{ provider.city }}{{ provider.city && provider.country ? ', ' : '' }}{{ provider.country }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de Contacto -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Información de Contacto
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="provider.contact_name">
                            <label class="text-sm font-medium text-muted-foreground">Persona de Contacto</label>
                            <p class="text-sm">{{ provider.contact_name }}</p>
                        </div>

                        <div v-if="provider.contact_email" class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Email de Contacto</label>
                                <p class="text-sm">{{ provider.contact_email }}</p>
                            </div>
                        </div>

                        <div v-if="provider.contact_phone" class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Teléfono de Contacto</label>
                                <p class="text-sm">{{ provider.contact_phone }}</p>
                            </div>
                        </div>

                        <div v-if="!provider.contact_name && !provider.contact_email && !provider.contact_phone" class="text-sm text-muted-foreground">
                            No hay información de contacto adicional
                        </div>
                    </CardContent>
                </Card>

                <!-- Categorías de Servicio -->
                <Card>
                    <CardHeader>
                        <CardTitle>Categorías de Servicio</CardTitle>
                        <CardDescription>Servicios que ofrece este proveedor</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="provider.categories.length > 0" class="flex flex-wrap gap-2">
                            <Badge
                                v-for="category in provider.categories"
                                :key="category.id"
                                variant="outline"
                                class="text-sm"
                            >
                                {{ category.name }}
                            </Badge>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No hay categorías asignadas
                        </p>
                    </CardContent>
                </Card>

                <!-- Notas -->
                <Card v-if="provider.notes">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Notas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">{{ provider.notes }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Sistema</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Creado</label>
                        <p class="text-sm">{{ new Date(provider.created_at).toLocaleString('es-CO') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Última actualización</label>
                        <p class="text-sm">{{ new Date(provider.updated_at).toLocaleString('es-CO') }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Toggle Status Dialog -->
        <AlertDialog v-model:open="showToggleDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ provider.is_active ? 'Desactivar' : 'Activar' }} Proveedor
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Estás seguro de que deseas {{ provider.is_active ? 'desactivar' : 'activar' }} a {{ provider.name }}?
                        {{ provider.is_active ? 'El proveedor no aparecerá en las listas de proveedores activos.' : 'El proveedor volverá a estar disponible.' }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="handleToggleStatus">
                        Confirmar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Delete Dialog -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Eliminar Proveedor</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Estás seguro de que deseas eliminar a {{ provider.name }}?
                        Esta acción no se puede deshacer.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="handleDelete"
                    >
                        Eliminar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
