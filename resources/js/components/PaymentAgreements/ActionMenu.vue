<template>
    <div class="relative inline-block text-left">
        <div>
            <button
                @click="isOpen = !isOpen"
                type="button"
                class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
            >
                Acciones
                <ChevronDown class="-mr-1 ml-2 h-4 w-4" />
            </button>
        </div>

        <div
            v-show="isOpen"
            @click.outside="isOpen = false"
            class="ring-opacity-5 absolute right-0 z-50 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black focus:outline-none"
        >
            <div class="py-1">
                <!-- Edit -->
                <Link
                    v-if="canEdit"
                    :href="route('payment-agreements.edit', agreement.id)"
                    class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                >
                    <Edit class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                    Editar
                </Link>

                <!-- Submit for Approval -->
                <button
                    v-if="agreement.status === 'draft'"
                    @click="submitForApproval"
                    class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                >
                    <Send class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                    Enviar para Aprobación
                </button>

                <!-- Approve -->
                <button
                    v-if="agreement.status === 'pending_approval'"
                    @click="approve"
                    class="group flex w-full items-center px-4 py-2 text-sm text-green-700 hover:bg-green-50 hover:text-green-900"
                >
                    <Check class="mr-3 h-4 w-4 text-green-400 group-hover:text-green-500" />
                    Aprobar
                </button>

                <!-- Activate -->
                <button
                    v-if="agreement.status === 'approved'"
                    @click="activate"
                    class="group flex w-full items-center px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 hover:text-blue-900"
                >
                    <Play class="mr-3 h-4 w-4 text-blue-400 group-hover:text-blue-500" />
                    Activar
                </button>
            </div>

            <div class="py-1">
                <!-- Download PDF -->
                <button
                    @click="downloadPdf"
                    class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                >
                    <Download class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                    Descargar PDF
                </button>
            </div>

            <div class="py-1">
                <!-- Cancel -->
                <button
                    v-if="canCancel"
                    @click="cancel"
                    class="group flex w-full items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900"
                >
                    <X class="mr-3 h-4 w-4 text-red-400 group-hover:text-red-500" />
                    Cancelar Acuerdo
                </button>

                <!-- Delete -->
                <button
                    v-if="canDelete"
                    @click="deleteAgreement"
                    class="group flex w-full items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900"
                >
                    <Trash2 class="mr-3 h-4 w-4 text-red-400 group-hover:text-red-500" />
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, ChevronDown, Download, Edit, Play, Send, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    agreement: Object,
});

const isOpen = ref(false);

const canEdit = computed(() => {
    return ['draft', 'pending_approval'].includes(props.agreement.status);
});

const canCancel = computed(() => {
    return !['completed', 'cancelled'].includes(props.agreement.status);
});

const canDelete = computed(() => {
    return ['draft', 'pending_approval'].includes(props.agreement.status);
});

const submitForApproval = () => {
    if (confirm('¿Está seguro de enviar este acuerdo para aprobación?')) {
        router.post(route('payment-agreements.submit-for-approval', props.agreement.id));
    }
    isOpen.value = false;
};

const approve = () => {
    if (confirm('¿Está seguro de aprobar este acuerdo de pago?')) {
        router.post(route('payment-agreements.approve', props.agreement.id));
    }
    isOpen.value = false;
};

const activate = () => {
    if (confirm('¿Está seguro de activar este acuerdo de pago?')) {
        router.post(route('payment-agreements.activate', props.agreement.id));
    }
    isOpen.value = false;
};

const cancel = () => {
    if (confirm('¿Está seguro de cancelar este acuerdo de pago? Esta acción no se puede deshacer.')) {
        router.post(route('payment-agreements.cancel', props.agreement.id));
    }
    isOpen.value = false;
};

const deleteAgreement = () => {
    if (confirm('¿Está seguro de eliminar este acuerdo de pago? Esta acción no se puede deshacer.')) {
        router.delete(route('payment-agreements.destroy', props.agreement.id));
    }
    isOpen.value = false;
};

const downloadPdf = () => {
    // TODO: Implement PDF download
    alert('Funcionalidad de PDF en desarrollo');
    isOpen.value = false;
};
</script>
