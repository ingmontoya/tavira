<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, Pencil, Send, User, XCircle, CheckCircle, Ban } from 'lucide-vue-next';

interface ProviderCategory {
    id: number;
    name: string;
}

interface QuotationResponse {
    id: number;
    provider_id: number;
    quotation_request_id: number;
    quoted_amount: number;
    proposal: string | null;
    estimated_days: number | null;
    attachments: string | null;
    status: 'pending' | 'accepted' | 'rejected';
    created_at: string;
    provider: {
        id: number;
        name: string;
        email: string;
        phone: string;
    };
}

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    requirements: string | null;
    status: 'draft' | 'published' | 'closed';
    created_by: number;
    published_at: string | null;
    closed_at: string | null;
    created_at: string;
    updated_at: string;
    createdBy: { id: number; name: string };
    categories: ProviderCategory[];
    responses: QuotationResponse[];
}

const props = defineProps<{
    quotationRequest: QuotationRequest;
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
        title: props.quotationRequest.title,
        href: `/quotation-requests/${props.quotationRequest.id}`,
    },
];

const publishRequest = () => {
    if (!confirm('¿Está seguro de publicar esta solicitud? Se notificará a los proveedores.')) return;

    router.post(`/quotation-requests/${props.quotationRequest.id}/publish`, {}, {
        preserveState: false,
    });
};

const closeRequest = () => {
    if (!confirm('¿Está seguro de cerrar esta solicitud? No se aceptarán más cotizaciones.')) return;

    router.post(`/quotation-requests/${props.quotationRequest.id}/close`, {}, {
        preserveState: false,
    });
};

const deleteRequest = () => {
    if (!confirm('¿Está seguro de eliminar esta solicitud? Esta acción no se puede deshacer.')) return;

    router.delete(`/quotation-requests/${props.quotationRequest.id}`);
};

const getStatusBadge = (status: string) => {
    const badges = {
        draft: { text: 'Borrador', class: 'bg-gray-100 text-gray-800' },
        published: { text: 'Publicado', class: 'bg-blue-100 text-blue-800' },
        closed: { text: 'Cerrado', class: 'bg-green-100 text-green-800' },
    };
    return badges[status] || badges.draft;
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(amount);
};

const approveResponse = (responseId: number) => {
    if (!confirm('¿Está seguro de aprobar esta cotización?')) return;

    router.post(`/quotation-requests/${props.quotationRequest.id}/responses/${responseId}/approve`, {}, {
        preserveState: false,
    });
};

const rejectResponse = (responseId: number) => {
    if (!confirm('¿Está seguro de rechazar esta cotización?')) return;

    router.post(`/quotation-requests/${props.quotationRequest.id}/responses/${responseId}/reject`, {}, {
        preserveState: false,
    });
};

const getResponseStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        accepted: { text: 'Aprobada', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazada', class: 'bg-red-100 text-red-800' },
    };
    return badges[status] || badges.pending;
};
</script>

<template>
    <Head :title="quotationRequest.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold">{{ quotationRequest.title }}</h1>
                        <Badge :class="getStatusBadge(quotationRequest.status).class">
                            {{ getStatusBadge(quotationRequest.status).text }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">Detalles de la solicitud de cotización</p>
                </div>
                <div class="flex space-x-2">
                    <Button variant="outline" @click="router.visit('/quotation-requests')">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <Button
                        v-if="quotationRequest.status === 'draft'"
                        variant="outline"
                        @click="router.visit(`/quotation-requests/${quotationRequest.id}/edit`)"
                    >
                        <Pencil class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                    <Button
                        v-if="quotationRequest.status === 'draft'"
                        @click="publishRequest"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Publicar
                    </Button>
                    <Button
                        v-if="quotationRequest.status === 'published'"
                        @click="closeRequest"
                    >
                        <FileText class="mr-2 h-4 w-4" />
                        Cerrar
                    </Button>
                    <Button
                        v-if="quotationRequest.status === 'draft'"
                        variant="destructive"
                        @click="deleteRequest"
                    >
                        <XCircle class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-4 md:col-span-2">
                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ quotationRequest.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Requirements -->
                    <Card v-if="quotationRequest.requirements">
                        <CardHeader>
                            <CardTitle>Requisitos Específicos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ quotationRequest.requirements }}</p>
                        </CardContent>
                    </Card>

                    <!-- Responses -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Cotizaciones Recibidas ({{ quotationRequest.responses.length }})</CardTitle>
                            <CardDescription>Respuestas de proveedores a esta solicitud</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="quotationRequest.responses.length > 0" class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Proveedor</TableHead>
                                            <TableHead>Monto</TableHead>
                                            <TableHead>Tiempo Estimado</TableHead>
                                            <TableHead>Notas</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead class="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="response in quotationRequest.responses" :key="response.id">
                                            <TableCell>
                                                <div>
                                                    <p class="font-medium">{{ response.provider.name }}</p>
                                                    <p class="text-sm text-muted-foreground">{{ response.provider.email }}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell class="font-semibold">
                                                {{ formatCurrency(response.quoted_amount) }}
                                            </TableCell>
                                            <TableCell>
                                                <span v-if="response.estimated_days">{{ response.estimated_days }} días</span>
                                                <span v-else class="text-muted-foreground">-</span>
                                            </TableCell>
                                            <TableCell>
                                                <p class="max-w-xs truncate">{{ response.proposal || '-' }}</p>
                                            </TableCell>
                                            <TableCell>
                                                <Badge :class="getResponseStatusBadge(response.status).class">
                                                    {{ getResponseStatusBadge(response.status).text }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{{ formatDate(response.created_at) }}</TableCell>
                                            <TableCell class="text-right">
                                                <div class="flex justify-end gap-2">
                                                    <Button
                                                        v-if="response.status === 'pending'"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="approveResponse(response.id)"
                                                    >
                                                        <CheckCircle class="mr-1 h-4 w-4" />
                                                        Aprobar
                                                    </Button>
                                                    <Button
                                                        v-if="response.status === 'pending'"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="rejectResponse(response.id)"
                                                    >
                                                        <Ban class="mr-1 h-4 w-4" />
                                                        Rechazar
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            <div v-else class="py-8 text-center text-muted-foreground">
                                <p>No se han recibido cotizaciones aún</p>
                                <p v-if="quotationRequest.status === 'draft'" class="text-sm">
                                    Publica la solicitud para que los proveedores puedan responder
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="quotationRequest.createdBy" class="flex items-start space-x-2">
                                <User class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Creado por</p>
                                    <p class="text-sm text-muted-foreground">{{ quotationRequest.createdBy.name }}</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha de creación</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(quotationRequest.created_at) }}</p>
                                </div>
                            </div>

                            <div v-if="quotationRequest.deadline" class="flex items-start space-x-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha límite</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(quotationRequest.deadline) }}</p>
                                </div>
                            </div>

                            <div v-if="quotationRequest.published_at" class="flex items-start space-x-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha de publicación</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(quotationRequest.published_at) }}</p>
                                </div>
                            </div>

                            <div v-if="quotationRequest.closed_at" class="flex items-start space-x-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha de cierre</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(quotationRequest.closed_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Categorías</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="category in quotationRequest.categories"
                                    :key="category.id"
                                    variant="outline"
                                >
                                    {{ category.name }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
