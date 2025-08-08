<template>
    <Head title="Nuevo Acuerdo de Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Crear Acuerdo de Pago</h1>
                <p class="mt-1 text-sm text-gray-600">Crea un nuevo acuerdo de pago para un propietario moroso</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Apartment Selection -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Selección de Apartamento</h3>

                    <div v-if="!selectedApartment" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700"> Apartamento Moroso </label>
                            <Select v-model="form.apartment_id" @update:model-value="loadApartmentData" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona un apartamento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="apartment in delinquentApartments" :key="apartment.id" :value="apartment.id.toString()">
                                        {{ apartment.full_address }} - Deuda: {{ formatCurrency(apartment.debt_amount) }}
                                        <span v-if="apartment.residents"> ({{ apartment.residents }})</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <div class="flex items-start">
                                <AlertTriangle class="mt-0.5 mr-3 h-5 w-5 text-yellow-400" />
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Apartamento: {{ selectedApartment.full_address }}</h4>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>
                                            <strong>Residentes:</strong>
                                            {{ selectedApartment.residents?.map((r) => r.name).join(', ') || 'No asignados' }}
                                        </p>
                                        <p><strong>Deuda total:</strong> {{ formatCurrency(apartmentDebt) }}</p>
                                        <p><strong>Facturas vencidas:</strong> {{ overdueInvoices.length }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="clearApartment" class="text-sm text-blue-600 hover:text-blue-800">Cambiar apartamento</button>
                    </div>
                </div>

                <!-- Agreement Details -->
                <div v-if="selectedApartment" class="rounded-lg bg-white p-6 shadow-sm">
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
                            <p class="mt-1 text-xs text-gray-500">Deuda detectada: {{ formatCurrency(apartmentDebt) }}</p>
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
                <div v-if="selectedApartment" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Términos y Condiciones</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="terms_and_conditions" class="block text-sm font-medium text-gray-700">
                                Términos y Condiciones del Acuerdo *
                            </label>
                            <textarea
                                id="terms_and_conditions"
                                v-model="form.terms_and_conditions"
                                rows="6"
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
                        :href="route('payment-agreements.index')"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    >
                        <span v-if="form.processing">Creando...</span>
                        <span v-else>Crear Acuerdo</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/utils/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    apartment: Object,
    apartmentDebt: Number,
    overdueInvoices: Array,
    delinquentApartments: Array,
});

const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Nuevo Acuerdo de Pago',
        href: '/payment-agreements/create',
    },
];

const form = useForm({
    apartment_id: props.apartment?.id || '',
    total_debt_amount: props.apartmentDebt || 0,
    initial_payment: null,
    monthly_payment: null,
    installments: 12,
    start_date: '',
    penalty_rate: 2.0,
    terms_and_conditions: getDefaultTerms(),
    notes: '',
});

const selectedApartment = ref(props.apartment);

const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const loadApartmentData = (apartmentId) => {
    if (apartmentId) {
        form.apartment_id = apartmentId;
        router.get(
            route('payment-agreements.create'),
            { apartment_id: apartmentId },
            {
                preserveState: true,
                only: ['apartment', 'apartmentDebt', 'overdueInvoices'],
                onSuccess: (page) => {
                    selectedApartment.value = page.props.apartment;
                    form.total_debt_amount = page.props.apartmentDebt || 0;
                },
            },
        );
    }
};

const clearApartment = () => {
    selectedApartment.value = null;
    form.apartment_id = '';
    form.total_debt_amount = 0;
};

const submit = () => {
    form.post(route('payment-agreements.store'));
};

function getDefaultTerms() {
    return `TÉRMINOS Y CONDICIONES DEL ACUERDO DE PAGO

        1. OBJETO DEL ACUERDO
        El presente acuerdo tiene por objeto establecer un plan de pagos para saldar la deuda pendiente por concepto de cuotas de administración.

        2. OBLIGACIONES DEL DEUDOR
        - Realizar los pagos puntualmente en las fechas establecidas
        - Mantener al día las cuotas corrientes de administración
        - Notificar cualquier dificultad de pago con anticipación

        3. PENALIZACIONES POR INCUMPLIMIENTO
        - El incumplimiento de dos (2) cuotas consecutivas constituirá causal de terminación del acuerdo
        - Se aplicará la tasa de penalización establecida por mora en los pagos
        - La terminación del acuerdo por incumplimiento hará exigible la totalidad de la deuda

        4. VIGENCIA
        El presente acuerdo entrará en vigencia una vez sea aprobado por la administración y tendrá la duración establecida en el cronograma de pagos.

        5. ACEPTACIÓN
        Las partes declaran conocer y aceptar los términos y condiciones establecidos en el presente acuerdo.`;
}
</script>
