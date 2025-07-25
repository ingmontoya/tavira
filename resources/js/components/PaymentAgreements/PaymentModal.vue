<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div @click="$emit('close')" class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"></div>

            <!-- Modal -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
            >
                <form @submit.prevent="submit">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <CreditCard class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="mt-3 flex-1 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Registrar Pago</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Registra un pago para una cuota del acuerdo {{ agreement.agreement_number }}</p>
                                </div>

                                <div class="mt-4 space-y-4">
                                    <!-- Installment Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700"> Cuota a Pagar * </label>
                                        <Select v-model="form.installment_id" @update:model-value="updateAmount" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona una cuota" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="installment in pendingInstallments"
                                                    :key="installment.id"
                                                    :value="installment.id.toString()"
                                                >
                                                    Cuota {{ installment.installment_number }} -
                                                    {{ formatCurrency(installment.remaining_amount) }}
                                                    (Vence: {{ formatDate(installment.due_date) }})
                                                    <span v-if="installment.is_overdue" class="text-red-600"> (VENCIDA)</span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Amount -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700"> Monto a Pagar * </label>
                                        <div class="relative mt-1 rounded-lg shadow-sm">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input
                                                v-model="form.amount"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                :max="maxAmount"
                                                class="block w-full rounded-lg border border-gray-300 py-2 pr-3 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                        <p v-if="maxAmount > 0" class="mt-1 text-xs text-gray-500">Máximo: {{ formatCurrency(maxAmount) }}</p>
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700"> Método de Pago </label>
                                        <Select v-model="form.payment_method">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un método" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="efectivo">Efectivo</SelectItem>
                                                <SelectItem value="transferencia">Transferencia Bancaria</SelectItem>
                                                <SelectItem value="cheque">Cheque</SelectItem>
                                                <SelectItem value="pse">PSE</SelectItem>
                                                <SelectItem value="tarjeta">Tarjeta de Crédito/Débito</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Payment Reference -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700"> Referencia de Pago </label>
                                        <input
                                            v-model="form.payment_reference"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            placeholder="Número de transacción, cheque, etc."
                                        />
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700"> Notas </label>
                                        <textarea
                                            v-model="form.notes"
                                            rows="2"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            placeholder="Notas adicionales sobre el pago..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.installment_id || !form.amount"
                            class="inline-flex w-full justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            <span v-if="form.processing">Registrando...</span>
                            <span v-else>Registrar Pago</span>
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency, formatDate } from '@/utils/format';
import { useForm } from '@inertiajs/vue3';
import { CreditCard } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    agreement: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    installment_id: '',
    amount: null,
    payment_method: '',
    payment_reference: '',
    notes: '',
});

const pendingInstallments = computed(() => {
    return props.agreement.installments
        .filter((installment) => installment.status !== 'paid' && installment.remaining_amount > 0)
        .sort((a, b) => a.installment_number - b.installment_number);
});

const maxAmount = computed(() => {
    if (!form.installment_id) return 0;
    const installment = props.agreement.installments.find((i) => i.id == form.installment_id);
    return installment ? installment.remaining_amount : 0;
});

const updateAmount = (installmentId) => {
    if (installmentId) {
        form.installment_id = installmentId;
        const installment = props.agreement.installments.find((i) => i.id == installmentId);
        if (installment) {
            form.amount = installment.remaining_amount;
        }
    }
};

const submit = () => {
    form.post(route('payment-agreements.record-payment', props.agreement.id), {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>
