<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Search, Package, Clock, CheckCircle2, XCircle } from 'lucide-vue-next';

interface Pqrs {
    id: number;
    ticket_number: string;
    type: string;
    subject: string;
    description: string;
    status: string;
    priority: string;
    created_at: string;
    responded_at: string | null;
    admin_response: string | null;
    apartment: {
        number: string;
    } | null;
}

interface Props {
    pqrs: Pqrs | null;
    ticket?: string;
}

const props = defineProps<Props>();

const form = useForm({
    ticket: props.ticket || '',
});

const search = () => {
    form.get(route('pqrs.track'), {
        preserveState: true,
    });
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

const statusIcons: Record<string, any> = {
    pendiente: Clock,
    en_revision: Package,
    en_proceso: Package,
    resuelta: CheckCircle2,
    cerrada: XCircle,
};
</script>

<template>
    <Head title="Rastrear PQRS" />

    <div class="min-h-screen bg-gradient-to-br from-purple-50 to-pink-100 py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <div class="mb-4 flex justify-center">
                    <div class="rounded-full bg-purple-600 p-4">
                        <Search class="h-8 w-8 text-white" />
                    </div>
                </div>
                <h1 class="mb-2 text-3xl font-bold text-gray-900">Rastrear PQRS</h1>
                <p class="text-lg text-gray-600">
                    Consulte el estado de su petición, queja, reclamo o sugerencia
                </p>
            </div>

            <!-- Search Form -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Buscar por Número de Ticket</CardTitle>
                    <CardDescription>
                        Ingrese el número de ticket que recibió al enviar su PQRS
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="search" class="flex gap-4">
                        <div class="flex-1 space-y-2">
                            <Label for="ticket" class="sr-only">Número de Ticket</Label>
                            <Input
                                id="ticket"
                                v-model="form.ticket"
                                type="text"
                                placeholder="Ej: PQRS-2025-00123"
                                required
                            />
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            <Search class="mr-2 h-4 w-4" />
                            Buscar
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Results -->
            <div v-if="pqrs">
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle>{{ pqrs.ticket_number }}</CardTitle>
                                <CardDescription class="mt-2">
                                    {{ typeLabels[pqrs.type] }} - Enviada el
                                    {{ new Date(pqrs.created_at).toLocaleDateString('es-CO') }}
                                </CardDescription>
                            </div>
                            <Badge :class="statusColors[pqrs.status]" class="flex items-center gap-1">
                                <component :is="statusIcons[pqrs.status]" class="h-3 w-3" />
                                {{ statusLabels[pqrs.status] }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Subject -->
                        <div>
                            <p class="text-sm font-medium text-gray-500">Asunto</p>
                            <p class="mt-1 text-base text-gray-900">{{ pqrs.subject }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <p class="text-sm font-medium text-gray-500">Descripción</p>
                            <p class="mt-1 text-base text-gray-900">{{ pqrs.description }}</p>
                        </div>

                        <!-- Apartment if available -->
                        <div v-if="pqrs.apartment">
                            <p class="text-sm font-medium text-gray-500">Apartamento/Casa</p>
                            <p class="mt-1 text-base text-gray-900">{{ pqrs.apartment.number }}</p>
                        </div>

                        <!-- Admin Response -->
                        <div
                            v-if="pqrs.admin_response"
                            class="rounded-lg border border-green-200 bg-green-50 p-4"
                        >
                            <div class="mb-2 flex items-center gap-2">
                                <CheckCircle2 class="h-5 w-5 text-green-600" />
                                <p class="font-semibold text-green-900">Respuesta de Administración</p>
                            </div>
                            <p class="text-sm text-green-800">{{ pqrs.admin_response }}</p>
                            <p v-if="pqrs.responded_at" class="mt-2 text-xs text-green-700">
                                Respondido el
                                {{ new Date(pqrs.responded_at).toLocaleDateString('es-CO') }}
                            </p>
                        </div>

                        <!-- No Response Yet -->
                        <div
                            v-else-if="pqrs.status !== 'cerrada'"
                            class="rounded-lg border border-blue-200 bg-blue-50 p-4"
                        >
                            <p class="text-sm text-blue-800">
                                Su PQRS está siendo procesada. Recibirá una notificación por correo
                                cuando la administración responda.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Not Found -->
            <Card v-else-if="ticket && !pqrs" class="border-red-200 bg-red-50">
                <CardContent class="pt-6">
                    <div class="text-center">
                        <XCircle class="mx-auto mb-4 h-12 w-12 text-red-600" />
                        <h3 class="mb-2 text-lg font-semibold text-red-900">PQRS No Encontrada</h3>
                        <p class="text-sm text-red-800">
                            No se encontró ninguna PQRS con el número de ticket proporcionado. Por
                            favor, verifique el número e intente nuevamente.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Help -->
            <Card class="mt-6 border-purple-200 bg-purple-50">
                <CardContent class="pt-6">
                    <h3 class="mb-3 font-semibold text-purple-900">¿Necesita ayuda?</h3>
                    <ul class="space-y-2 text-sm text-purple-800">
                        <li>• El número de ticket fue enviado a su correo electrónico.</li>
                        <li>• Asegúrese de ingresar el número completo (Ej: PQRS-2025-00123).</li>
                        <li>
                            • Si no encuentra su PQRS, puede enviar una nueva desde el formulario
                            público.
                        </li>
                    </ul>
                    <div class="mt-4">
                        <Button variant="outline" as-child>
                            <a :href="route('pqrs.public.create')">Enviar Nueva PQRS</a>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
