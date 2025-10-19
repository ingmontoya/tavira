<script setup lang="ts">
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Calendar, CheckCircle, Eye, FileCheck, FileText, Pencil, Send, User, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

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
    expense_id: number | null;
    expense: {
        id: number;
        expense_number: string;
        status: string;
        total_amount: number;
    } | null;
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

// Dialog states
const selectedResponseId = ref<number | null>(null);
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

const publishRequest = () => {
    if (!confirm('¿Está seguro de publicar esta solicitud? Se notificará a los proveedores.')) return;

    router.post(
        `/quotation-requests/${props.quotationRequest.id}/publish`,
        {},
        {
            preserveState: false,
        },
    );
};

const closeRequest = () => {
    if (!confirm('¿Está seguro de cerrar esta solicitud? No se aceptarán más cotizaciones.')) return;

    router.post(
        `/quotation-requests/${props.quotationRequest.id}/close`,
        {},
        {
            preserveState: false,
        },
    );
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

const openApproveDialog = (responseId: number) => {
    selectedResponseId.value = responseId;
    showApproveDialog.value = true;
};

const openRejectDialog = (responseId: number) => {
    selectedResponseId.value = responseId;
    showRejectDialog.value = true;
};

const approveResponse = () => {
    if (!selectedResponseId.value) return;

    router.post(
        `/quotation-requests/${props.quotationRequest.id}/responses/${selectedResponseId.value}/approve`,
        {},
        {
            preserveState: false,
            onSuccess: () => {
                showApproveDialog.value = false;
                selectedResponseId.value = null;
            },
        },
    );
};

const rejectResponse = () => {
    if (!selectedResponseId.value) return;

    router.post(
        `/quotation-requests/${props.quotationRequest.id}/responses/${selectedResponseId.value}/reject`,
        {},
        {
            preserveState: false,
            onSuccess: () => {
                showRejectDialog.value = false;
                selectedResponseId.value = null;
            },
        },
    );
};

const getResponseStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800' },
        accepted: { text: 'Aprobada', class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazada', class: 'bg-red-100 text-red-800' },
    };
    return badges[status] || badges.pending;
};

const getSelectedResponse = () => {
    return props.quotationRequest.responses.find((r) => r.id === selectedResponseId.value);
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
                    <Button v-if="quotationRequest.status === 'draft'" @click="publishRequest">
                        <Send class="mr-2 h-4 w-4" />
                        Publicar
                    </Button>
                    <Button v-if="quotationRequest.status === 'published'" @click="closeRequest">
                        <FileText class="mr-2 h-4 w-4" />
                        Cerrar
                    </Button>
                    <Button v-if="quotationRequest.status === 'draft'" variant="destructive" @click="deleteRequest">
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
                            <div v-if="quotationRequest.responses.length > 0" class="space-y-3">
                                <div
                                    v-for="response in quotationRequest.responses"
                                    :key="response.id"
                                    class="rounded-lg border p-4 transition-colors hover:bg-muted/30"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <!-- Provider Info & Status -->
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <p class="font-semibold">{{ response.provider.name }}</p>
                                                    <p class="text-sm text-muted-foreground">{{ response.provider.email }}</p>
                                                </div>
                                                <Badge :class="getResponseStatusBadge(response.status).class">
                                                    {{ getResponseStatusBadge(response.status).text }}
                                                </Badge>
                                            </div>

                                            <!-- Amount & Details -->
                                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-medium">Monto:</span>
                                                    <span class="font-semibold text-green-700">{{ formatCurrency(response.quoted_amount) }}</span>
                                                </div>
                                                <div v-if="response.estimated_days" class="flex items-center gap-1">
                                                    <span class="font-medium">Tiempo:</span>
                                                    <span>{{ response.estimated_days }} días</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <Calendar class="h-3 w-3" />
                                                    <span class="text-muted-foreground">{{ formatDate(response.created_at) }}</span>
                                                </div>
                                            </div>

                                            <!-- Proposal Preview -->
                                            <div v-if="response.proposal" class="text-sm">
                                                <p class="line-clamp-2 text-muted-foreground">{{ response.proposal }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Expense Link (if created) -->
                                    <div
                                        v-if="response.expense"
                                        class="mt-3 flex items-center gap-2 rounded-md border border-green-200 bg-green-50 p-2"
                                    >
                                        <FileCheck class="h-4 w-4 text-green-600" />
                                        <span class="text-sm font-medium text-green-900">Gasto creado:</span>
                                        <Button
                                            size="sm"
                                            variant="link"
                                            class="h-auto p-0 text-green-700 hover:text-green-900"
                                            @click="router.visit(route('expenses.show', response.expense.id))"
                                        >
                                            {{ response.expense.expense_number }}
                                        </Button>
                                        <Badge
                                            class="ml-auto"
                                            :class="
                                                response.expense.status === 'aprobado'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : response.expense.status === 'pendiente_concejo'
                                                      ? 'bg-orange-100 text-orange-800'
                                                      : response.expense.status === 'pagado'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-yellow-100 text-yellow-800'
                                            "
                                        >
                                            {{
                                                response.expense.status === 'aprobado'
                                                    ? 'Aprobado'
                                                    : response.expense.status === 'pendiente_concejo'
                                                      ? 'Pendiente Concejo'
                                                      : response.expense.status === 'pagado'
                                                        ? 'Pagado'
                                                        : response.expense.status === 'pendiente'
                                                          ? 'Pendiente'
                                                          : response.expense.status
                                            }}
                                        </Badge>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-3 flex flex-wrap gap-2 border-t pt-3">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="router.visit(route('quotation-requests.responses.show', [quotationRequest.id, response.id]))"
                                        >
                                            <Eye class="mr-1 h-4 w-4" />
                                            Ver Detalles
                                        </Button>
                                        <Button
                                            v-if="response.status === 'pending'"
                                            size="sm"
                                            class="bg-green-600 hover:bg-green-700"
                                            @click="openApproveDialog(response.id)"
                                        >
                                            <CheckCircle class="mr-1 h-4 w-4" />
                                            Aprobar
                                        </Button>
                                        <Button
                                            v-if="response.status === 'pending'"
                                            size="sm"
                                            variant="destructive"
                                            @click="openRejectDialog(response.id)"
                                        >
                                            <Ban class="mr-1 h-4 w-4" />
                                            Rechazar
                                        </Button>
                                    </div>
                                </div>
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
                                <Badge v-for="category in quotationRequest.categories" :key="category.id" variant="outline">
                                    {{ category.name }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Approve Dialog -->
        <AlertDialog v-model:open="showApproveDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Aprobar Propuesta y Crear Gasto</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Está seguro de que desea aprobar esta propuesta de <strong>{{ getSelectedResponse()?.provider.name }}</strong
                        >? <br /><br />
                        <strong>Esta acción:</strong>
                        <ul class="mt-2 ml-4 list-disc text-sm">
                            <li>Creará automáticamente un gasto en el sistema</li>
                            <li>El gasto seguirá el flujo de aprobación correspondiente</li>
                            <li>Notificará al proveedor seleccionado</li>
                            <li>Rechazará automáticamente las demás propuestas</li>
                        </ul>
                        <br />
                        <strong>Monto:</strong> {{ getSelectedResponse() ? formatCurrency(getSelectedResponse()!.quoted_amount) : '' }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="approveResponse" class="bg-green-600 hover:bg-green-700"> Aprobar y Crear Gasto </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Reject Dialog -->
        <AlertDialog v-model:open="showRejectDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Rechazar Propuesta</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Está seguro de que desea rechazar esta propuesta de <strong>{{ getSelectedResponse()?.provider.name }}</strong
                        >? <br /><br />
                        Esta acción no se puede deshacer.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="rejectResponse" class="bg-red-600 hover:bg-red-700"> Confirmar Rechazo </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
