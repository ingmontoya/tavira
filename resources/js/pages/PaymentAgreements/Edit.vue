<template>
    <div class="space-y-6">
        <div class="flex items-center space-x-3">
            <Link :href="route('payment-agreements.show', agreement.id)" class="text-gray-400 hover:text-gray-600">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Editar Acuerdo {{ agreement.agreement_number }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ agreement.apartment.full_address }}
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Agreement Details -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Detalles del Acuerdo</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="total_debt_amount" class="block text-sm font-medium text-gray-700"> Monto Total de la Deuda * </label>
                        <div class="relative mt-1 rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input
                                id="total_debt_amount"
                                v-model="form.total_debt_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="block w-full rounded-lg border border-gray-300 py-2 pr-3 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="0.00"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label for="initial_payment" class="block text-sm font-medium text-gray-700"> Pago Inicial </label>
                        <div class="relative mt-1 rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input
                                id="initial_payment"
                                v-model="form.initial_payment"
                                type="number"
                                step="0.01"
                                min="0"
                                class="block w-full rounded-lg border border-gray-300 py-2 pr-3 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="monthly_payment" class="block text-sm font-medium text-gray-700"> Cuota Mensual * </label>
                        <div class="relative mt-1 rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input
                                id="monthly_payment"
                                v-model="form.monthly_payment"
                                type="number"
                                step="0.01"
                                min="0"
                                class="block w-full rounded-lg border border-gray-300 py-2 pr-3 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="0.00"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label for="installments" class="block text-sm font-medium text-gray-700"> Número de Cuotas * </label>
                        <input
                            id="installments"
                            v-model="form.installments"
                            type="number"
                            min="1"
                            max="60"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="12"
                            required
                        />
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700"> Fecha de Inicio * </label>
                        <input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            :min="today"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            required
                        />
                    </div>

                    <div>
                        <label for="penalty_rate" class="block text-sm font-medium text-gray-700"> Tasa de Penalización (%) </label>
                        <input
                            id="penalty_rate"
                            v-model="form.penalty_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="2.0"
                        />
                        <p class="mt-1 text-xs text-gray-500">Tasa mensual aplicada por incumplimiento</p>
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Términos y Condiciones</h3>

                <div class="space-y-4">
                    <div>
                        <label for="terms_and_conditions" class="block text-sm font-medium text-gray-700">
                            Términos y Condiciones del Acuerdo *
                        </label>
                        <textarea
                            id="terms_and_conditions"
                            v-model="form.terms_and_conditions"
                            rows="8"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="Describe los términos y condiciones del acuerdo de pago..."
                            required
                        ></textarea>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700"> Notas Adicionales </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="Notas adicionales sobre el acuerdo..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <Link
                    :href="route('payment-agreements.show', agreement.id)"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                    Cancelar
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                >
                    <span v-if="form.processing">Actualizando...</span>
                    <span v-else>Actualizar Acuerdo</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    agreement: Object,
});

const form = useForm({
    total_debt_amount: props.agreement.total_debt_amount,
    initial_payment: props.agreement.initial_payment,
    monthly_payment: props.agreement.monthly_payment,
    installments: props.agreement.installments,
    start_date: props.agreement.start_date,
    penalty_rate: props.agreement.penalty_rate,
    terms_and_conditions: props.agreement.terms_and_conditions,
    notes: props.agreement.notes,
});

const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const submit = () => {
    form.put(route('payment-agreements.update', props.agreement.id));
};
</script>
