<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { Calendar, FileText, User, Send, ArrowLeft, CheckCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface QuotationRequest {
    id: number;
    title: string;
    description: string;
    deadline: string | null;
    requirements: string | null;
    status: string;
    created_at: string;
    categories: Category[];
    created_by: {
        name: string;
        email: string;
    };
}

interface ExistingResponse {
    id: number;
    quoted_amount: number;
    proposal: string | null;
    estimated_days: number | null;
    status: string;
    created_at: string;
}

interface Props {
    request: QuotationRequest;
    existingResponse: ExistingResponse | null;
    canRespond: boolean;
    tenant_id: string;
}

const props = defineProps<Props>();

const showResponseDialog = ref(false);

const form = useForm({
    quoted_amount: props.existingResponse?.quoted_amount || 0,
    proposal: props.existingResponse?.proposal || '',
    estimated_days: props.existingResponse?.estimated_days || null,
});

const submitResponse = () => {
    form.post(route('provider.quotations.respond', [props.tenant_id, props.request.id]), {
        onSuccess: () => {
            showResponseDialog.value = false;
        },
    });
};

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
        published: { text: 'Publicada', variant: 'default' as const },
        pending: { text: 'Pendiente', variant: 'secondary' as const },
        accepted: { text: 'Aceptada', variant: 'default' as const },
        rejected: { text: 'Rechazada', variant: 'destructive' as const },
    };
    return badges[status as keyof typeof badges] || badges.published;
};

const isExpired = (deadline: string | null): boolean => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
};
</script>

<template>
    <AppLayout title="Solicitud de Cotización">
        <Head :title="`Solicitud: ${request.title}`" />

        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('provider.quotations.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold">{{ request.title }}</h1>
                        <p class="text-muted-foreground">
                            Solicitud de cotización
                        </p>
                    </div>
                </div>
                <Badge :variant="getStatusBadge(request.status).variant" class="text-sm">
                    {{ getStatusBadge(request.status).text }}
                </Badge>
            </div>

            <!-- Alert if already responded -->
            <Card v-if="existingResponse" class="border-green-200 bg-green-50">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="h-5 w-5 text-green-600" />
                        <CardTitle class="text-green-900">Ya respondiste a esta solicitud</CardTitle>
                    </div>
                </CardHeader>
                <CardContent class="text-green-800">
                    <p class="text-sm">
                        Enviaste tu propuesta el {{ formatDate(existingResponse.created_at) }}
                    </p>
                    <div class="mt-4 space-y-2">
                        <p><strong>Precio ofrecido:</strong> {{ formatCurrency(existingResponse.quoted_amount) }}</p>
                        <p v-if="existingResponse.estimated_days">
                            <strong>Tiempo estimado:</strong> {{ existingResponse.estimated_days }} días
                        </p>
                        <p v-if="existingResponse.proposal">
                            <strong>Notas:</strong> {{ existingResponse.proposal }}
                        </p>
                        <Badge :variant="getStatusBadge(existingResponse.status).variant" class="mt-2">
                            Estado: {{ getStatusBadge(existingResponse.status).text }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Request Details -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-2 space-y-6">
                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ request.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Requirements -->
                    <Card v-if="request.requirements">
                        <CardHeader>
                            <CardTitle>Requisitos Adicionales</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ request.requirements }}</p>
                        </CardContent>
                    </Card>

                    <!-- Response Form -->
                    <Card v-if="canRespond && !existingResponse">
                        <CardHeader>
                            <CardTitle>Enviar Propuesta</CardTitle>
                            <CardDescription>
                                Completa el formulario para enviar tu cotización
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="showResponseDialog = true" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="quoted_amount">Precio Ofrecido (COP) *</Label>
                                    <Input
                                        id="quoted_amount"
                                        v-model.number="form.quoted_amount"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        required
                                        placeholder="Ej: 500000"
                                    />
                                    <p v-if="form.quoted_amount > 0" class="text-sm text-muted-foreground">
                                        {{ formatCurrency(form.quoted_amount) }}
                                    </p>
                                    <p v-if="form.errors.quoted_amount" class="text-sm text-destructive">
                                        {{ form.errors.quoted_amount }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="estimated_days">Tiempo Estimado de Entrega (días)</Label>
                                    <Input
                                        id="estimated_days"
                                        v-model.number="form.estimated_days"
                                        type="number"
                                        min="0"
                                        placeholder="Ej: 15"
                                    />
                                    <p v-if="form.errors.estimated_days" class="text-sm text-destructive">
                                        {{ form.errors.estimated_days }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="proposal">Notas Adicionales</Label>
                                    <Textarea
                                        id="proposal"
                                        v-model="form.proposal"
                                        rows="4"
                                        placeholder="Incluye cualquier detalle importante sobre tu propuesta..."
                                    />
                                    <p v-if="form.errors.proposal" class="text-sm text-destructive">
                                        {{ form.errors.proposal }}
                                    </p>
                                </div>

                                <AlertDialog v-model:open="showResponseDialog">
                                    <AlertDialogTrigger as-child>
                                        <Button type="button" class="w-full" :disabled="form.processing">
                                            <Send class="mr-2 h-4 w-4" />
                                            Enviar Propuesta
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Confirmar Envío de Propuesta</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                ¿Estás seguro de que deseas enviar esta propuesta?
                                                <br /><br />
                                                <strong>Precio:</strong> {{ formatCurrency(form.quoted_amount) }}
                                                <br />
                                                <strong v-if="form.estimated_days">Tiempo estimado:</strong>
                                                {{ form.estimated_days }} días
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                            <AlertDialogAction @click="submitResponse">
                                                Confirmar y Enviar
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </form>
                        </CardContent>
                    </Card>

                    <Card v-else-if="!canRespond && !existingResponse" class="border-yellow-200 bg-yellow-50">
                        <CardContent class="pt-6">
                            <p class="text-yellow-900">
                                <strong>Esta solicitud ya no acepta respuestas.</strong>
                                <span v-if="isExpired(request.deadline)">
                                    La fecha límite ha expirado.
                                </span>
                                <span v-else>
                                    La solicitud ha sido cerrada.
                                </span>
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Request Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha Límite</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ request.deadline ? formatDate(request.deadline) : 'No especificada' }}
                                    </p>
                                    <Badge v-if="isExpired(request.deadline)" variant="destructive" class="mt-1">
                                        Expirada
                                    </Badge>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <FileText class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Categorías</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <Badge v-for="category in request.categories" :key="category.id" variant="secondary">
                                            {{ category.name }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <User class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Creado por</p>
                                    <p class="text-sm text-muted-foreground">{{ request.created_by.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ request.created_by.email }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Fecha de Publicación</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(request.created_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
