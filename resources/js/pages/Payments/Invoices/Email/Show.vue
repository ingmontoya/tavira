<script setup lang="ts">
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
    AlertTriangle, ArrowLeft, CheckCircle, Clock, Edit, Eye, 
    Mail, MailCheck, MailX, RefreshCw, Send, Trash2, 
    User, XCircle 
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import type { InvoiceEmailBatch } from '@/types';

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
    {
        title: 'Detalle del Lote',
        href: '#',
    },
];

interface Props {
    batch: InvoiceEmailBatch;
}

const props = defineProps<Props>();

// Get page data for errors and flash messages
const page = usePage();
const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Real-time updates
const refreshInterval = ref<number | null>(null);

// Dialog states
const showSendDialog = ref(false);
const showCancelDialog = ref(false);
const showRetryDialog = ref(false);
const showDeleteDialog = ref(false);

// Delete batch functions
const openDeleteDialog = () => {
    showDeleteDialog.value = true;
};

const confirmDeleteBatch = () => {
    showDeleteDialog.value = false;
    router.delete(`/invoices/email/${props.batch.id}`, {
        onSuccess: () => {
            router.visit('/invoices/email');
        },
    });
};

// Retry failed delivery
const retryDelivery = (deliveryId: number) => {
    router.post(`/invoices/email/delivery/${deliveryId}/retry`);
};

// Refresh data
const refreshData = () => {
    router.reload({ only: ['batch'] });
};

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

// Get delivery status icon
const getDeliveryStatusIcon = (status: string) => {
    switch (status) {
        case 'pendiente':
            return Clock;
        case 'enviado':
            return MailCheck;
        case 'fallido':
        case 'rebotado':
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
const getProgressPercentage = computed(() => {
    if (props.batch.total_invoices === 0) return 0;
    return Math.round((props.batch.sent_count / props.batch.total_invoices) * 100);
});

// Group deliveries by status
const deliveriesByStatus = computed(() => {
    const groups = {
        pending: props.batch.deliveries.filter(d => d.status === 'pendiente'),
        sent: props.batch.deliveries.filter(d => d.status === 'enviado'),
        failed: props.batch.deliveries.filter(d => d.status === 'fallido'),
        bounced: props.batch.deliveries.filter(d => d.status === 'rebotado'),
    };
    return groups;
});

// Auto-refresh for active batches
onMounted(() => {
    if (props.batch.status === 'procesando') {
        refreshInterval.value = window.setInterval(refreshData, 5000); // Refresh every 5 seconds
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});

// Stop auto-refresh if batch completes
computed(() => {
    if (refreshInterval.value && props.batch.status !== 'procesando') {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
});

// Send batch functions
const openSendDialog = () => {
    showSendDialog.value = true;
};

const confirmSendBatch = () => {
    showSendDialog.value = false;
    router.post(`/invoices/email/${props.batch.id}/send`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Auto-refresh will start if status changes to 'procesando'
            refreshData();
        },
        onError: (errors) => {
            console.error('Error sending batch:', errors);
        }
    });
};

// Cancel batch functions
const openCancelDialog = () => {
    showCancelDialog.value = true;
};

const confirmCancelBatch = () => {
    showCancelDialog.value = false;
    router.post(`/invoices/email/${props.batch.id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            refreshData();
        }
    });
};

// Retry batch functions
const openRetryDialog = () => {
    showRetryDialog.value = true;
};

const confirmRetryBatch = () => {
    showRetryDialog.value = false;
    router.post(`/invoices/email/${props.batch.id}/retry`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            refreshData();
        }
    });
};

// Check if batch can be sent
const canSend = computed(() => {
    return ['draft', 'scheduled'].includes(props.batch.status);
});

// Check if batch can be cancelled
const canCancel = computed(() => {
    return props.batch.can_be_cancelled;
});

// Check if batch can be retried
const canRetry = computed(() => {
    return props.batch.can_be_restarted;
});
</script>

<template>
    <Head :title="`Lote: ${batch.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ batch.name }}</h1>
                    <p class="text-muted-foreground">
                        {{ batch.description || 'Lote de envío de facturas por email' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Send Button -->
                    <Button 
                        v-if="canSend" 
                        @click="openSendDialog" 
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Enviar Lote
                    </Button>
                    
                    <!-- Cancel Button -->
                    <Button 
                        v-if="canCancel" 
                        @click="openCancelDialog" 
                        variant="destructive"
                    >
                        <XCircle class="mr-2 h-4 w-4" />
                        Cancelar
                    </Button>
                    
                    <!-- Retry Button -->
                    <Button 
                        v-if="canRetry" 
                        @click="openRetryDialog" 
                        variant="outline"
                    >
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Reintentar
                    </Button>
                    
                    <Button @click="refreshData" variant="outline" size="sm">
                        <RefreshCw class="h-4 w-4" />
                    </Button>
                    
                    <Button variant="outline" asChild>
                        <Link href="/invoices/email">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </div>
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

            <!-- Status and Progress Section -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Status Card -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg">Estado</CardTitle>
                            <component 
                                :is="getStatusIcon(batch.status)" 
                                class="h-5 w-5 text-muted-foreground" 
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Badge :class="batch.status_badge.class" class="mb-2">
                            {{ batch.status_badge.text }}
                        </Badge>
                        
                        <div class="text-sm text-muted-foreground space-y-1">
                            <div>Creado: {{ formatDate(batch.created_at) }}</div>
                            <div v-if="batch.sent_at">Enviado: {{ formatDate(batch.sent_at) }}</div>
                            <div v-if="batch.completed_at">Completado: {{ formatDate(batch.completed_at) }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Progress Card -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-lg">Progreso</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span>Enviadas</span>
                                <span>{{ batch.sent_count }} / {{ batch.total_invoices }}</span>
                            </div>
                            <Progress :value="getProgressPercentage" class="w-full" />
                            <div class="text-xs text-muted-foreground text-center">
                                {{ getProgressPercentage }}% completado
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics Card -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-lg">Estadísticas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="flex items-center">
                                    <MailCheck class="h-3 w-3 mr-1 text-green-600" />
                                    Enviadas
                                </span>
                                <span class="font-medium text-green-600">{{ batch.sent_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="flex items-center">
                                    <MailX class="h-3 w-3 mr-1 text-red-600" />
                                    Fallidas
                                </span>
                                <span class="font-medium text-red-600">{{ batch.failed_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="flex items-center">
                                    <Clock class="h-3 w-3 mr-1 text-orange-600" />
                                    Pendientes
                                </span>
                                <span class="font-medium text-orange-600">{{ batch.pending_count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Actions -->
            <Card v-if="batch.can_edit || batch.can_delete" class="p-4">
                <div class="flex items-center gap-2">
                    <Button 
                        v-if="batch.can_edit"
                        variant="outline"
                        asChild
                    >
                        <Link :href="`/invoices/email/${batch.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>

                    <Button 
                        v-if="batch.can_delete"
                        variant="outline"
                        @click="openDeleteDialog"
                        class="text-red-600 hover:text-red-700"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                </div>
            </Card>

            <!-- Auto-refresh indicator -->
            <Alert v-if="batch.status === 'procesando'">
                <RefreshCw class="h-4 w-4 animate-spin" />
                <AlertDescription>
                    El lote se está procesando. Esta página se actualiza automáticamente cada 5 segundos.
                </AlertDescription>
            </Alert>

            <!-- Delivery Details Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <Mail class="h-5 w-5" />
                        <span>Detalle de Entregas</span>
                    </CardTitle>
                    <CardDescription>
                        Estado detallado de cada envío de factura por email
                    </CardDescription>
                </CardHeader>
                
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Factura</TableHead>
                                    <TableHead>Apartamento</TableHead>
                                    <TableHead>Destinatario</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha Envío</TableHead>
                                    <TableHead>Reintentos</TableHead>
                                    <TableHead>Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="batch.deliveries.length">
                                    <TableRow
                                        v-for="delivery in batch.deliveries"
                                        :key="delivery.id"
                                        class="transition-colors hover:bg-muted/50"
                                    >
                                        <TableCell>
                                            <div class="font-medium">{{ delivery.invoice.invoice_number }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                ${{ parseFloat(delivery.invoice.balance_due.toString()).toLocaleString() }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="font-medium">{{ delivery.invoice.apartment?.full_address || 'Sin apartamento asignado' }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                #{{ delivery.invoice.apartment?.number || 'N/A' }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center space-x-2">
                                                <User class="h-3 w-3 text-muted-foreground" />
                                                <div>
                                                    <div class="text-sm">{{ delivery.recipient_name || 'Sin nombre' }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ delivery.recipient_email }}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center space-x-2">
                                                <component 
                                                    :is="getDeliveryStatusIcon(delivery.status)" 
                                                    class="h-4 w-4"
                                                />
                                                <Badge :class="delivery.status_badge.class">
                                                    {{ delivery.status_badge.text }}
                                                </Badge>
                                            </div>
                                            <div v-if="delivery.error_message" class="text-xs text-red-600 mt-1">
                                                {{ delivery.error_message }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="delivery.sent_at" class="text-sm">
                                                {{ formatDate(delivery.sent_at) }}
                                            </div>
                                            <div v-else class="text-sm text-muted-foreground">
                                                Pendiente
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm">
                                                {{ delivery.retry_count }} / {{ delivery.max_retries }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="router.visit(`/invoices/${delivery.invoice.id}`)"
                                                >
                                                    <Eye class="h-3 w-3" />
                                                </Button>
                                                
                                                <Button
                                                    v-if="delivery.can_retry && (delivery.status === 'fallido' || delivery.status === 'rebotado')"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="retryDelivery(delivery.id)"
                                                    class="text-orange-600 hover:text-orange-700"
                                                >
                                                    <RefreshCw class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell colSpan="7" class="h-24 text-center">
                                            No hay entregas en este lote.
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Error Summary -->
            <Card v-if="batch.failed_count > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2 text-red-600">
                        <AlertTriangle class="h-5 w-5" />
                        <span>Resumen de Errores</span>
                    </CardTitle>
                    <CardDescription>
                        {{ batch.failed_count }} entregas fallaron en este lote
                    </CardDescription>
                </CardHeader>
                
                <CardContent>
                    <div class="space-y-2">
                        <div 
                            v-for="delivery in deliveriesByStatus.failed" 
                            :key="delivery.id"
                            class="flex items-center justify-between p-3 border rounded-lg bg-red-50"
                        >
                            <div>
                                <div class="font-medium text-sm">{{ delivery.invoice.invoice_number }}</div>
                                <div class="text-xs text-muted-foreground">{{ delivery.recipient_email }}</div>
                                <div class="text-xs text-red-600">{{ delivery.error_message }}</div>
                            </div>
                            <Button
                                v-if="delivery.can_retry"
                                variant="outline"
                                size="sm"
                                @click="retryDelivery(delivery.id)"
                            >
                                <RefreshCw class="mr-2 h-3 w-3" />
                                Reintentar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Batch Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Lote</CardTitle>
                </CardHeader>
                
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Creado por</Label>
                            <div class="flex items-center space-x-2">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">{{ batch.created_by.name }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Total de facturas</Label>
                            <div class="text-sm">{{ batch.total_invoices }} facturas</div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Fecha de creación</Label>
                            <div class="text-sm">{{ formatDate(batch.created_at) }}</div>
                        </div>

                        <div v-if="batch.updated_at !== batch.created_at" class="space-y-2">
                            <Label class="text-sm font-medium">Última actualización</Label>
                            <div class="text-sm">{{ formatDate(batch.updated_at) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Send Confirmation Dialog -->
        <Dialog v-model:open="showSendDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center space-x-2">
                        <Send class="h-5 w-5 text-green-600" />
                        <span>Confirmar Envío de Lote</span>
                    </DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que quieres enviar este lote de emails?
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 space-y-2">
                    <div class="text-sm">
                        <strong>Lote:</strong> {{ batch.name }}
                    </div>
                    <div class="text-sm">
                        <strong>Total de facturas:</strong> {{ batch.total_invoices }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Una vez iniciado el envío, este proceso no se puede deshacer.
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showSendDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="confirmSendBatch" class="bg-green-600 hover:bg-green-700">
                        <Send class="mr-2 h-4 w-4" />
                        Sí, Enviar Lote
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Cancel Confirmation Dialog -->
        <Dialog v-model:open="showCancelDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center space-x-2">
                        <XCircle class="h-5 w-5 text-red-600" />
                        <span>Confirmar Cancelación</span>
                    </DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que quieres cancelar este lote?
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 space-y-2">
                    <div class="text-sm">
                        <strong>Lote:</strong> {{ batch.name }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Esta acción detendrá todos los envíos pendientes y no se puede deshacer.
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showCancelDialog = false">
                        No, Mantener
                    </Button>
                    <Button variant="destructive" @click="confirmCancelBatch">
                        <XCircle class="mr-2 h-4 w-4" />
                        Sí, Cancelar Lote
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Retry Confirmation Dialog -->
        <Dialog v-model:open="showRetryDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center space-x-2">
                        <RefreshCw class="h-5 w-5 text-orange-600" />
                        <span>Confirmar Reintento</span>
                    </DialogTitle>
                    <DialogDescription>
                        ¿Quieres reintentar los envíos fallidos de este lote?
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 space-y-2">
                    <div class="text-sm">
                        <strong>Lote:</strong> {{ batch.name }}
                    </div>
                    <div class="text-sm">
                        <strong>Envíos fallidos:</strong> {{ batch.failed_count }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Esto intentará enviar nuevamente solo los emails que fallaron.
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRetryDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="confirmRetryBatch" variant="outline">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Sí, Reintentar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center space-x-2">
                        <Trash2 class="h-5 w-5 text-red-600" />
                        <span>Confirmar Eliminación</span>
                    </DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que deseas eliminar este lote de envío?
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 space-y-2">
                    <div class="text-sm">
                        <strong>Lote:</strong> {{ batch.name }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Esta acción no se puede deshacer. Se eliminará permanentemente el lote y todos sus registros de envío.
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="confirmDeleteBatch">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Sí, Eliminar Lote
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>