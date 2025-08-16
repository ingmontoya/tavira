import type { BatchProgressData, CreateInvoiceEmailBatchData, InvoiceEmailBatch, InvoiceEmailBatchFilters } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export function useInvoiceEmailBatches() {
    // State
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Filter and navigate to batches
    const navigateWithFilters = (filters: InvoiceEmailBatchFilters, page?: number) => {
        const params: Record<string, string> = {};

        // Add filters to params
        if (filters.search) params.search = filters.search;
        if (filters.status && filters.status !== 'all') params.status = filters.status;
        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        if (page) params.page = page.toString();

        router.get('/invoices/email', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Create a new batch
    const createBatch = (data: CreateInvoiceEmailBatchData) => {
        return new Promise((resolve, reject) => {
            isLoading.value = true;
            error.value = null;

            router.post('/invoices/email', data, {
                onSuccess: (page) => {
                    isLoading.value = false;
                    resolve(page);
                },
                onError: (errors) => {
                    isLoading.value = false;
                    error.value = Object.values(errors).flat().join(', ');
                    reject(errors);
                },
            });
        });
    };

    // Send a batch
    const sendBatch = (batchId: number) => {
        return new Promise((resolve, reject) => {
            isLoading.value = true;
            error.value = null;

            router.post(
                `/invoices/email/${batchId}/send`,
                {},
                {
                    onSuccess: (page) => {
                        isLoading.value = false;
                        resolve(page);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).flat().join(', ');
                        reject(errors);
                    },
                },
            );
        });
    };

    // Delete a batch
    const deleteBatch = (batchId: number) => {
        return new Promise((resolve, reject) => {
            isLoading.value = true;
            error.value = null;

            router.delete(`/invoices/email/${batchId}`, {
                onSuccess: (page) => {
                    isLoading.value = false;
                    resolve(page);
                },
                onError: (errors) => {
                    isLoading.value = false;
                    error.value = Object.values(errors).flat().join(', ');
                    reject(errors);
                },
            });
        });
    };

    // Get batch progress data
    const getBatchProgress = (batch: InvoiceEmailBatch): BatchProgressData => {
        const total = batch.total_invoices;
        const sent = batch.sent_count;
        const failed = batch.failed_count;
        const pending = batch.pending_count;

        const percentage = total > 0 ? Math.round((sent / total) * 100) : 0;

        let current_status = '';
        switch (batch.status) {
            case 'borrador':
                current_status = 'Lote en borrador';
                break;
            case 'listo':
                current_status = 'Listo para enviar';
                break;
            case 'procesando':
                current_status = 'Enviando facturas...';
                break;
            case 'completado':
                current_status = 'Envío completado';
                break;
            case 'con_errores':
                current_status = `Completado con ${failed} errores`;
                break;
            default:
                current_status = 'Estado desconocido';
        }

        return {
            total,
            sent,
            failed,
            pending,
            percentage,
            current_status,
        };
    };

    // Filter status options
    const statusOptions = [
        { value: 'all', label: 'Todos los estados' },
        { value: 'borrador', label: 'Borrador' },
        { value: 'listo', label: 'Listo para enviar' },
        { value: 'procesando', label: 'Procesando' },
        { value: 'completado', label: 'Completado' },
        { value: 'con_errores', label: 'Con errores' },
    ];

    // Computed helpers
    const hasError = computed(() => error.value !== null);
    const canCreateBatch = computed(() => !isLoading.value);

    // Clear error
    const clearError = () => {
        error.value = null;
    };

    // Refresh current page
    const refresh = () => {
        router.reload({ only: ['batches'] });
    };

    return {
        // State
        isLoading: computed(() => isLoading.value),
        error: computed(() => error.value),
        hasError,
        canCreateBatch,

        // Actions
        navigateWithFilters,
        createBatch,
        sendBatch,
        deleteBatch,
        getBatchProgress,
        clearError,
        refresh,

        // Helpers
        statusOptions,
    };
}

export function useInvoiceEmailDeliveries() {
    // State
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Retry a failed delivery
    const retryDelivery = (deliveryId: number) => {
        return new Promise((resolve, reject) => {
            isLoading.value = true;
            error.value = null;

            router.post(
                `/invoices/email/delivery/${deliveryId}/retry`,
                {},
                {
                    onSuccess: (page) => {
                        isLoading.value = false;
                        resolve(page);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).flat().join(', ');
                        reject(errors);
                    },
                },
            );
        });
    };

    // Retry all failed deliveries in a batch
    const retryBatchFailures = (batchId: number) => {
        return new Promise((resolve, reject) => {
            isLoading.value = true;
            error.value = null;

            router.post(
                `/invoices/email/${batchId}/retry-failures`,
                {},
                {
                    onSuccess: (page) => {
                        isLoading.value = false;
                        resolve(page);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).flat().join(', ');
                        reject(errors);
                    },
                },
            );
        });
    };

    // Get delivery status icon and class
    const getDeliveryStatusConfig = (status: string) => {
        switch (status) {
            case 'pendiente':
                return {
                    icon: 'Clock',
                    class: 'bg-orange-100 text-orange-800 border-orange-200',
                    color: 'orange',
                };
            case 'enviado':
                return {
                    icon: 'MailCheck',
                    class: 'bg-green-100 text-green-800 border-green-200',
                    color: 'green',
                };
            case 'fallido':
            case 'rebotado':
                return {
                    icon: 'MailX',
                    class: 'bg-red-100 text-red-800 border-red-200',
                    color: 'red',
                };
            default:
                return {
                    icon: 'Clock',
                    class: 'bg-gray-100 text-gray-800 border-gray-200',
                    color: 'gray',
                };
        }
    };

    // Format date helper
    const formatDeliveryDate = (dateString: string | null) => {
        if (!dateString) return null;

        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Computed helpers
    const hasError = computed(() => error.value !== null);

    // Clear error
    const clearError = () => {
        error.value = null;
    };

    return {
        // State
        isLoading: computed(() => isLoading.value),
        error: computed(() => error.value),
        hasError,

        // Actions
        retryDelivery,
        retryBatchFailures,
        clearError,

        // Helpers
        getDeliveryStatusConfig,
        formatDeliveryDate,
    };
}
