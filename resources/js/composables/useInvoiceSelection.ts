import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { 
    Invoice, 
    EligibleInvoiceFilters, 
    EligibleInvoicesResponse 
} from '@/types';

export function useInvoiceSelection() {
    // State
    const selectedInvoiceIds = ref<number[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Filter and navigate to eligible invoices
    const navigateWithFilters = (filters: EligibleInvoiceFilters, page?: number) => {
        const params: Record<string, string> = {};

        // Add filters to params
        if (filters.search) params.search = filters.search;
        if (filters.apartment_id && filters.apartment_id !== 'all') 
            params.apartment_id = filters.apartment_id;
        if (filters.status && filters.status !== 'all') 
            params.status = filters.status;
        if (filters.type && filters.type !== 'all') 
            params.type = filters.type;
        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        if (page) params.page = page.toString();

        router.get('/invoices/email/create', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Selection management
    const selectInvoice = (invoiceId: number) => {
        if (!selectedInvoiceIds.value.includes(invoiceId)) {
            selectedInvoiceIds.value.push(invoiceId);
        }
    };

    const deselectInvoice = (invoiceId: number) => {
        const index = selectedInvoiceIds.value.indexOf(invoiceId);
        if (index > -1) {
            selectedInvoiceIds.value.splice(index, 1);
        }
    };

    const toggleInvoiceSelection = (invoiceId: number) => {
        if (selectedInvoiceIds.value.includes(invoiceId)) {
            deselectInvoice(invoiceId);
        } else {
            selectInvoice(invoiceId);
        }
    };

    const selectAll = (invoices: Invoice[]) => {
        const allIds = invoices.map(invoice => invoice.id);
        selectedInvoiceIds.value = [...new Set([...selectedInvoiceIds.value, ...allIds])];
    };

    const deselectAll = (invoices?: Invoice[]) => {
        if (invoices) {
            const idsToRemove = invoices.map(invoice => invoice.id);
            selectedInvoiceIds.value = selectedInvoiceIds.value.filter(
                id => !idsToRemove.includes(id)
            );
        } else {
            selectedInvoiceIds.value = [];
        }
    };

    const setSelectedInvoices = (invoiceIds: number[]) => {
        selectedInvoiceIds.value = [...invoiceIds];
    };

    // Selection state helpers
    const isInvoiceSelected = (invoiceId: number) => {
        return selectedInvoiceIds.value.includes(invoiceId);
    };

    const areAllSelected = (invoices: Invoice[]) => {
        return invoices.every(invoice => selectedInvoiceIds.value.includes(invoice.id));
    };

    const areSomeSelected = (invoices: Invoice[]) => {
        return invoices.some(invoice => selectedInvoiceIds.value.includes(invoice.id)) &&
               !areAllSelected(invoices);
    };

    // Calculate statistics for selected invoices
    const getSelectedInvoicesStats = (allInvoices: Invoice[]) => {
        const selected = allInvoices.filter(invoice => 
            selectedInvoiceIds.value.includes(invoice.id)
        );
        
        const totalAmount = selected.reduce((sum, invoice) => 
            sum + parseFloat(invoice.balance_due.toString()), 0
        );
        
        const statusCounts = selected.reduce((counts, invoice) => {
            counts[invoice.status] = (counts[invoice.status] || 0) + 1;
            return counts;
        }, {} as Record<string, number>);

        const typeCounts = selected.reduce((counts, invoice) => {
            counts[invoice.type] = (counts[invoice.type] || 0) + 1;
            return counts;
        }, {} as Record<string, number>);

        return {
            count: selected.length,
            totalAmount,
            invoices: selected,
            statusCounts,
            typeCounts,
        };
    };

    // Filter options
    const statusFilterOptions = [
        { value: 'all', label: 'Todos los estados' },
        { value: 'pending', label: 'Pendiente' },
        { value: 'partial', label: 'Pago parcial' },
        { value: 'overdue', label: 'Vencido' },
    ];

    const typeFilterOptions = [
        { value: 'all', label: 'Todos los tipos' },
        { value: 'monthly', label: 'Mensual' },
        { value: 'individual', label: 'Individual' },
        { value: 'late_fee', label: 'Intereses' },
    ];

    // Invoice eligibility helpers
    const isInvoiceEligible = (invoice: Invoice) => {
        // Check if invoice can be sent via email
        return invoice.can_send_email && 
               ['pending', 'partial', 'overdue'].includes(invoice.status) &&
               parseFloat(invoice.balance_due.toString()) > 0;
    };

    const getIneligibilityReason = (invoice: Invoice) => {
        if (!invoice.can_send_email) {
            return 'No se puede enviar por email';
        }
        if (!['pending', 'partial', 'overdue'].includes(invoice.status)) {
            return 'Estado no válido para envío';
        }
        if (parseFloat(invoice.balance_due.toString()) <= 0) {
            return 'No hay saldo pendiente';
        }
        return null;
    };

    // Computed properties
    const selectedCount = computed(() => selectedInvoiceIds.value.length);
    const hasSelection = computed(() => selectedCount.value > 0);
    const hasError = computed(() => error.value !== null);

    // Clear functions
    const clearSelection = () => {
        selectedInvoiceIds.value = [];
    };

    const clearError = () => {
        error.value = null;
    };

    // Reset state
    const reset = () => {
        clearSelection();
        clearError();
        isLoading.value = false;
    };

    return {
        // State
        selectedInvoiceIds: computed(() => selectedInvoiceIds.value),
        selectedCount,
        hasSelection,
        isLoading: computed(() => isLoading.value),
        error: computed(() => error.value),
        hasError,

        // Actions
        navigateWithFilters,
        selectInvoice,
        deselectInvoice,
        toggleInvoiceSelection,
        selectAll,
        deselectAll,
        setSelectedInvoices,
        clearSelection,
        clearError,
        reset,

        // Helpers
        isInvoiceSelected,
        areAllSelected,
        areSomeSelected,
        getSelectedInvoicesStats,
        isInvoiceEligible,
        getIneligibilityReason,

        // Filter options
        statusFilterOptions,
        typeFilterOptions,
    };
}