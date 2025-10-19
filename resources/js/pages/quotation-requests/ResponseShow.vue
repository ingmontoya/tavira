<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import { ArrowLeft, Building2, Calendar, CheckCircle, Clock, DollarSign, FileText, Mail, Phone, User, Download } from 'lucide-vue-next';
import { ref } from 'vue';

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    requirements: string | null;
    deadline: string | null;
    status: string;
    created_at: string;
}

interface Provider {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    tax_id: string | null;
}

interface QuotationResponse {
    id: number;
    quoted_amount: number;
    proposal: string | null;
    estimated_days: number | null;
    attachments: string | null;
    status: 'pending' | 'accepted' | 'rejected';
    created_at: string;
    admin_notes: string | null;
}

interface Props {
    quotationRequest: QuotationRequest;
    response: QuotationResponse;
    provider: Provider;
}

const props = defineProps<Props>();

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const getStatusBadge = (status: string) => {
    const badges = {
        pending: { text: 'Pendiente', variant: 'secondary' as const, class: 'bg-blue-100 text-blue-800' },
        accepted: { text: 'Aprobada', variant: 'default' as const, class: 'bg-green-100 text-green-800' },
        rejected: { text: 'Rechazada', variant: 'destructive' as const, class: 'bg-red-100 text-red-800' },
    };
    return badges[status as keyof typeof badges] || badges.pending;
};

const approveResponse = () => {
    router.post(route('quotation-requests.responses.approve', [props.quotationRequest.id, props.response.id]), {}, {
        preserveState: false,
        onSuccess: () => {
            showApproveDialog.value = false;
        },
    });
};

const rejectResponse = () => {
    router.post(route('quotation-requests.responses.reject', [props.quotationRequest.id, props.response.id]), {}, {
        preserveState: false,
        onSuccess: () => {
            showRejectDialog.value = false;
        },
    });
};

const breadcrumbs = [
    { title: 'Solicitudes de Cotización', href: route('quotation-requests.index') },
    { title: props.quotationRequest.title, href: route('quotation-requests.show', props.quotationRequest.id) },
    { title: 'Propuesta', href: route('quotation-requests.responses.show', [props.quotationRequest.id, props.response.id]) },
];
</script>

<template>
    <AppLayout title="Detalles de Propuesta" :breadcrumbs="breadcrumbs">
        <Head :title="`Propuesta de ${provider.name}`" />

        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotation-requests.show', quotationRequest.id)">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold">Propuesta de {{ provider.name }}</h1>
                        <p class="text-muted-foreground">
                            Para: {{ quotationRequest.title }}
                        </p>
                    </div>
                </div>
                <Badge :class="getStatusBadge(response.status).class" class="text-sm">
                    {{ getStatusBadge(response.status).text }}
                </Badge>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Main Content -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Proposal Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Propuesta</CardTitle>
                            <CardDescription>
                                Información financiera y técnica de la cotización
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Price -->
                            <div class="rounded-lg border bg-gradient-to-br from-green-50 to-emerald-50 p-6">
                                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <DollarSign class="h-4 w-4" />
                                    Precio Cotizado
                                </div>
                                <div class="mt-2 text-4xl font-bold text-green-700">
                                    {{ formatCurrency(response.quoted_amount) }}
                                </div>
                            </div>

                            <!-- Estimated Days -->
                            <div v-if="response.estimated_days" class="flex items-center gap-3 rounded-lg border p-4">
                                <Clock class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Tiempo Estimado de Entrega</p>
                                    <p class="text-2xl font-semibold">{{ response.estimated_days }} días</p>
                                </div>
                            </div>

                            <!-- Proposal Notes -->
                            <div v-if="response.proposal">
                                <h3 class="mb-2 text-sm font-medium text-muted-foreground">Notas de la Propuesta</h3>
                                <div class="whitespace-pre-wrap rounded-lg border bg-muted/30 p-4">
                                    {{ response.proposal }}
                                </div>
                            </div>

                            <!-- Attachments -->
                            <div v-if="response.attachments">
                                <h3 class="mb-2 text-sm font-medium text-muted-foreground">Documentos Adjuntos</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between rounded-lg border p-3">
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-5 w-5 text-blue-600" />
                                            <div>
                                                <p class="text-sm font-medium">Documento de Propuesta</p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ response.attachments.split('/').pop() }}
                                                </p>
                                            </div>
                                        </div>
                                        <a
                                            :href="route('quotation-requests.responses.download', [quotationRequest.id, response.id])"
                                            target="_blank"
                                            download
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Download class="mr-2 h-4 w-4" />
                                                Descargar
                                            </Button>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Notes (if rejected) -->
                            <div v-if="response.admin_notes && response.status === 'rejected'" class="rounded-lg border border-red-200 bg-red-50 p-4">
                                <h3 class="mb-2 text-sm font-medium text-red-900">Notas de Rechazo</h3>
                                <p class="text-sm text-red-800">{{ response.admin_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <Card v-if="response.status === 'pending'">
                        <CardHeader>
                            <CardTitle>Acciones</CardTitle>
                            <CardDescription>
                                Aprobar o rechazar esta propuesta
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex gap-3">
                                <AlertDialog v-model:open="showApproveDialog">
                                    <AlertDialogTrigger as-child>
                                        <Button class="flex-1 bg-green-600 hover:bg-green-700">
                                            <CheckCircle class="mr-2 h-4 w-4" />
                                            Aprobar Propuesta
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Aprobar Propuesta</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                ¿Está seguro de que desea aprobar esta propuesta de <strong>{{ provider.name }}</strong>?
                                                <br /><br />
                                                El proveedor será notificado por correo electrónico y las demás propuestas serán rechazadas automáticamente.
                                                <br /><br />
                                                <strong>Monto:</strong> {{ formatCurrency(response.quoted_amount) }}
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                            <AlertDialogAction @click="approveResponse" class="bg-green-600 hover:bg-green-700">
                                                Confirmar Aprobación
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <AlertDialog v-model:open="showRejectDialog">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="destructive" class="flex-1">
                                            Rechazar Propuesta
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Rechazar Propuesta</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                ¿Está seguro de que desea rechazar esta propuesta?
                                                <br /><br />
                                                Esta acción no se puede deshacer.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                            <AlertDialogAction @click="rejectResponse" class="bg-red-600 hover:bg-red-700">
                                                Confirmar Rechazo
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Provider Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Proveedor</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-2">
                                <Building2 class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Nombre</p>
                                    <p class="text-sm text-muted-foreground">{{ provider.name }}</p>
                                </div>
                            </div>

                            <div v-if="provider.tax_id" class="flex items-start gap-2">
                                <FileText class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">NIT</p>
                                    <p class="text-sm text-muted-foreground">{{ provider.tax_id }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <Mail class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Email</p>
                                    <p class="text-sm text-muted-foreground">{{ provider.email }}</p>
                                </div>
                            </div>

                            <div v-if="provider.phone" class="flex items-start gap-2">
                                <Phone class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Teléfono</p>
                                    <p class="text-sm text-muted-foreground">{{ provider.phone }}</p>
                                </div>
                            </div>

                            <div v-if="provider.address" class="flex items-start gap-2">
                                <Building2 class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Dirección</p>
                                    <p class="text-sm text-muted-foreground">{{ provider.address }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Fechas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Propuesta Enviada</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(response.created_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
