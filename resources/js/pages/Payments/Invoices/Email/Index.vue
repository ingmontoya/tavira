<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle, Clock, Filter, Mail, MailCheck, MailX, Plus, Search, Send, Trash2, X, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { InvoiceEmailBatch, InvoiceEmailBatchFilters, InvoiceEmailBatchResponse } from '@/types';

// Breadcrumbs
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Pagos',
        href: '/payments',
    },
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Envío por Email',
        href: '/invoices/email',
    },
];

interface Props {
    batches: InvoiceEmailBatchResponse;
    filters?: InvoiceEmailBatchFilters;
}

const props = defineProps<Props>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Custom filters state
const customFilters = ref<InvoiceEmailBatchFilters>({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

// Apply filters
const applyFilters = () => {
    const params: Record<string, string> = {};

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.status && customFilters.value.status !== 'all') params.status = customFilters.value.status;
    if (customFilters.value.date_from) params.date_from = customFilters.value.date_from;
    if (customFilters.value.date_to) params.date_to = customFilters.value.date_to;

    router.get('/invoices/email', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Navigate to specific page
const goToPage = (page: number) => {
    const params: Record<string, string> = { page: page.toString() };

    if (customFilters.value.search) params.search = customFilters.value.search;
    if (customFilters.value.status && customFilters.value.status !== 'all') params.status = customFilters.value.status;
    if (customFilters.value.date_from) params.date_from = customFilters.value.date_from;
    if (customFilters.value.date_to) params.date_to = customFilters.value.date_to;

    router.get('/invoices/email', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    customFilters.value = {
        search: '',
        status: 'all',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

// Delete batch
const deleteBatch = (batch: InvoiceEmailBatch) => {
    if (confirm('¿Estás seguro de que deseas eliminar este lote de envío?')) {
        router.delete(`/invoices/email/${batch.id}`);
    }
};

// Send batch
const sendBatch = (batch: InvoiceEmailBatch) => {
    if (confirm(`¿Estás seguro de que deseas enviar el lote "${batch.name}" con ${batch.total_invoices} facturas?`)) {
        router.post(`/invoices/email/${batch.id}/send`);
    }
};

// Watch for filter changes
watch(
    customFilters,
    () => {
        applyFilters();
    },
    { deep: true },
);

// Status options
const statusOptions = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'borrador', label: 'Borrador' },
    { value: 'listo', label: 'Listo para enviar' },
    { value: 'procesando', label: 'Procesando' },
    { value: 'completado', label: 'Completado' },
    { value: 'con_errores', label: 'Con errores' },
];

// Get status icon
const getStatusIcon = (status: string) => {
    switch (status) {
        case 'borrador':
            return Clock;
        case 'listo':
            return Mail;
        case 'procesando':
            return Send;
        case 'completado':
            return MailCheck;
        case 'con_errores':
            return MailX;
        default:
            return Clock;
    }
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Calculate progress percentage
const getProgressPercentage = (batch: InvoiceEmailBatch) => {
    if (batch.total_invoices === 0) return 0;
    return Math.round((batch.sent_count / batch.total_invoices) * 100);
};
</script>

<template>
    <Head title="Envío de Facturas por Email" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Envío de Facturas por Email</h1>
                    <p class="text-muted-foreground">
                        Gestiona los lotes de envío de facturas por correo electrónico
                    </p>
                </div>

                <Button asChild>
                    <Link href="/invoices/email/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear Nuevo Lote
                    </Link>
                </Button>
            </div>

            <!-- Flash Messages -->
            <Alert v-if="flashSuccess" class="mb-4">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flashSuccess }}</AlertDescription>
            </Alert>

            <Alert v-if="flashError" variant="destructive" class="mb-4">
                <XCircle class="h-4 w-4" />
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <!-- Validation Errors -->
            <ValidationErrors :errors="errors" />

            <!-- Filters -->
            <Card class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-4 w-4" />
                        <Label class="text-sm font-medium">Filtros</Label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <div class="relative">
                                <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                <Input 
                                    id="search" 
                                    placeholder="Nombre del lote..." 
                                    v-model="customFilters.search" 
                                    class="pl-8" 
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <Select v-model="customFilters.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="status in statusOptions" 
                                        :key="status.value" 
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date From -->
                        <div class="space-y-2">
                            <Label for="date_from">Fecha desde</Label>
                            <Input 
                                id="date_from" 
                                type="date" 
                                v-model="customFilters.date_from" 
                            />
                        </div>

                        <!-- Date To -->
                        <div class="space-y-2">
                            <Label for="date_to">Fecha hasta</Label>
                            <Input 
                                id="date_to" 
                                type="date" 
                                v-model="customFilters.date_to" 
                            />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center space-x-2">
                        <Button @click="clearFilters" variant="outline" size="sm">
                            <X class="mr-2 h-4 w-4" />
                            Limpiar Filtros
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Batch Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card 
                    v-for="batch in props.batches.data" 
                    :key="batch.id"
                    class="transition-all hover:shadow-md"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <component 
                                    :is="getStatusIcon(batch.status)" 
                                    class="h-5 w-5 text-muted-foreground" 
                                />
                                <CardTitle class="text-lg">{{ batch.name }}</CardTitle>
                            </div>
                            <Badge :class="batch.status_badge.class">
                                {{ batch.status_badge.text }}
                            </Badge>
                        </div>
                        <CardDescription v-if="batch.description">
                            {{ batch.description }}
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent class="space-y-4">
                        <!-- Statistics -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold">{{ batch.total_invoices }}</div>
                                <div class="text-sm text-muted-foreground">Total facturas</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ batch.sent_count }}</div>
                                <div class="text-sm text-muted-foreground">Enviadas</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div v-if="batch.status !== 'borrador'" class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Progreso</span>
                                <span>{{ getProgressPercentage(batch) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${getProgressPercentage(batch)}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Error Count -->
                        <div v-if="batch.failed_count > 0" class="flex items-center space-x-2 text-red-600">
                            <MailX class="h-4 w-4" />
                            <span class="text-sm">{{ batch.failed_count }} errores</span>
                        </div>

                        <!-- Dates -->
                        <div class="space-y-1 text-sm text-muted-foreground">
                            <div>Creado: {{ formatDate(batch.created_at) }}</div>
                            <div v-if="batch.sent_at">Enviado: {{ formatDate(batch.sent_at) }}</div>
                            <div v-if="batch.completed_at">Completado: {{ formatDate(batch.completed_at) }}</div>
                        </div>
                    </CardContent>

                    <CardFooter class="pt-0">
                        <div class="flex w-full gap-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                class="flex-1"
                                @click="router.visit(`/invoices/email/${batch.id}`)"
                            >
                                Ver Detalles
                            </Button>
                            
                            <Button 
                                v-if="batch.can_send && batch.status === 'listo'"
                                size="sm"
                                @click="sendBatch(batch)"
                            >
                                <Send class="mr-2 h-3 w-3" />
                                Enviar
                            </Button>

                            <Button 
                                v-if="batch.can_delete"
                                variant="outline" 
                                size="sm"
                                @click="deleteBatch(batch)"
                                class="text-red-600 hover:text-red-700"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>
                    </CardFooter>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="props.batches.data.length === 0" class="p-12 text-center">
                <Mail class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" />
                <h3 class="text-lg font-semibold mb-2">No hay lotes de envío</h3>
                <p class="text-muted-foreground mb-4">
                    Crea tu primer lote de envío de facturas para comenzar
                </p>
                <Button asChild>
                    <Link href="/invoices/email/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear Primer Lote
                    </Link>
                </Button>
            </Card>

            <!-- Pagination -->
            <div v-if="props.batches.last_page > 1" class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ props.batches.from || 0 }} a {{ props.batches.to || 0 }} de {{ props.batches.total }} lotes
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.batches.current_page <= 1"
                        @click="goToPage(props.batches.current_page - 1)"
                    >
                        Anterior
                    </Button>

                    <span class="px-2 text-sm text-muted-foreground">
                        Página {{ props.batches.current_page }} de {{ props.batches.last_page }}
                    </span>

                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.batches.current_page >= props.batches.last_page"
                        @click="goToPage(props.batches.current_page + 1)"
                    >
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>