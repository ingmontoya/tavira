<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
    MessageSquare,
    User,
    Home,
    Calendar,
    ArrowLeft,
    Save,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Pqrs {
    id: number;
    ticket_number: string;
    type: string;
    subject: string;
    description: string;
    status: string;
    priority: string;
    submitter_name: string;
    submitter_email: string;
    submitter_phone: string | null;
    user: { name: string; email: string } | null;
    apartment: {
        id: number;
        number: string;
        apartment_type: { name: string };
    } | null;
    assigned_to: number | null;
    admin_response: string | null;
    responded_at: string | null;
    resolved_at: string | null;
    created_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    pqrs: Pqrs;
    users: User[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'PQRS', href: route('pqrs.index') },
    { label: props.pqrs.ticket_number, href: '#', current: true },
];

const form = useForm({
    status: props.pqrs.status,
    priority: props.pqrs.priority,
    assigned_to: props.pqrs.assigned_to,
    admin_response: props.pqrs.admin_response || '',
});

const updatePqrs = () => {
    form.patch(route('pqrs.update', props.pqrs.id), {
        preserveScroll: true,
    });
};

const deletePqrs = () => {
    router.delete(route('pqrs.destroy', props.pqrs.id));
};

const typeLabels: Record<string, string> = {
    peticion: 'Petición',
    queja: 'Queja',
    reclamo: 'Reclamo',
    sugerencia: 'Sugerencia',
};

const statusLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_revision: 'En Revisión',
    en_proceso: 'En Proceso',
    resuelta: 'Resuelta',
    cerrada: 'Cerrada',
};

const statusColors: Record<string, string> = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    en_revision: 'bg-blue-100 text-blue-800',
    en_proceso: 'bg-purple-100 text-purple-800',
    resuelta: 'bg-green-100 text-green-800',
    cerrada: 'bg-gray-100 text-gray-800',
};

const priorityLabels: Record<string, string> = {
    baja: 'Baja',
    media: 'Media',
    alta: 'Alta',
    urgente: 'Urgente',
};
</script>

<template>

    <Head :title="`PQRS - ${pqrs.ticket_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="route('pqrs.index')">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </a>
                    </Button>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ pqrs.ticket_number }}
                            </h1>
                            <Badge :class="statusColors[pqrs.status]">
                                {{ statusLabels[pqrs.status] }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ typeLabels[pqrs.type] }} -
                            {{ new Date(pqrs.created_at).toLocaleDateString('es-CO') }}
                        </p>
                    </div>
                </div>
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <Button variant="destructive" size="sm">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>¿Está seguro?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Esta acción no se puede deshacer. La PQRS será eliminada
                                permanentemente.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction @click="deletePqrs">Eliminar</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- PQRS Details -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                    <MessageSquare class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <CardTitle>{{ pqrs.subject }}</CardTitle>
                                    <CardDescription class="mt-1">
                                        {{ typeLabels[pqrs.type] }}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="prose max-w-none">
                                <p class="whitespace-pre-wrap text-gray-700">
                                    {{ pqrs.description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Response Form -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Gestionar PQRS</CardTitle>
                            <CardDescription>
                                Actualice el estado, prioridad y respuesta de la PQRS
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="updatePqrs" class="space-y-4">
                                <!-- Status -->
                                <div class="space-y-2">
                                    <Label for="status">Estado</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione el estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pendiente">Pendiente</SelectItem>
                                            <SelectItem value="en_revision">En Revisión</SelectItem>
                                            <SelectItem value="en_proceso">En Proceso</SelectItem>
                                            <SelectItem value="resuelta">Resuelta</SelectItem>
                                            <SelectItem value="cerrada">Cerrada</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Priority -->
                                <div class="space-y-2">
                                    <Label for="priority">Prioridad</Label>
                                    <Select v-model="form.priority">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione la prioridad" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="baja">Baja</SelectItem>
                                            <SelectItem value="media">Media</SelectItem>
                                            <SelectItem value="alta">Alta</SelectItem>
                                            <SelectItem value="urgente">Urgente</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Assigned To -->
                                <div class="space-y-2">
                                    <Label for="assigned_to">Asignar a</Label>
                                    <Select v-model="form.assigned_to">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un usuario" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">Sin asignar</SelectItem>
                                            <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Admin Response -->
                                <div class="space-y-2">
                                    <Label for="admin_response">Respuesta de Administración</Label>
                                    <Textarea id="admin_response" v-model="form.admin_response"
                                        placeholder="Escriba la respuesta a esta PQRS..." rows="6" />
                                    <p v-if="pqrs.responded_at" class="text-sm text-gray-600">
                                        Última respuesta:
                                        {{ new Date(pqrs.responded_at).toLocaleString('es-CO') }}
                                    </p>
                                </div>

                                <div class="flex justify-end">
                                    <Button type="submit" :disabled="form.processing">
                                        <Save class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Submitter Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <User class="h-4 w-4" />
                                Información del Solicitante
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Nombre</p>
                                <p class="mt-1 text-sm text-gray-900">{{ pqrs.submitter_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Correo</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a :href="`mailto:${pqrs.submitter_email}`" class="text-blue-600 hover:underline">
                                        {{ pqrs.submitter_email }}
                                    </a>
                                </p>
                            </div>
                            <div v-if="pqrs.submitter_phone">
                                <p class="text-sm font-medium text-gray-500">Teléfono</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a :href="`tel:${pqrs.submitter_phone}`" class="text-blue-600 hover:underline">
                                        {{ pqrs.submitter_phone }}
                                    </a>
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Apartment Information -->
                    <Card v-if="pqrs.apartment">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Home class="h-4 w-4" />
                                Información del Inmueble
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Número</p>
                                <p class="mt-1 text-sm text-gray-900">{{ pqrs.apartment.number }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tipo</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ pqrs.apartment.apartment_type.name }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Calendar class="h-4 w-4" />
                                Cronología
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="h-2 w-2 rounded-full bg-blue-600"></div>
                                    <div class="h-full w-px bg-gray-200"></div>
                                </div>
                                <div class="pb-4">
                                    <p class="text-sm font-medium text-gray-900">PQRS Creada</p>
                                    <p class="text-xs text-gray-600">
                                        {{ new Date(pqrs.created_at).toLocaleString('es-CO') }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="pqrs.responded_at" class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="h-2 w-2 rounded-full bg-green-600"></div>
                                    <div v-if="pqrs.resolved_at" class="h-full w-px bg-gray-200"></div>
                                </div>
                                <div :class="{ 'pb-4': pqrs.resolved_at }">
                                    <p class="text-sm font-medium text-gray-900">
                                        Respuesta Enviada
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        {{ new Date(pqrs.responded_at).toLocaleString('es-CO') }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="pqrs.resolved_at" class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="h-2 w-2 rounded-full bg-purple-600"></div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">PQRS Resuelta</p>
                                    <p class="text-xs text-gray-600">
                                        {{ new Date(pqrs.resolved_at).toLocaleString('es-CO') }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
