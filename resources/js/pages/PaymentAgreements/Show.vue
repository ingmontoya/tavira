<template>
    <Head title="Nuevo Acuerdo de Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <Link :href="route('payment-agreements.index')" class="text-gray-400 hover:text-gray-600">
                            <ArrowLeft class="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Acuerdo {{ agreement.agreement_number }}</h1>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ agreement.apartment.full_address }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span :class="['inline-flex items-center rounded-full px-3 py-1 text-sm font-medium', agreement.status_badge.class]">
                        {{ agreement.status_badge.text }}
                    </span>
                    <ActionMenu :agreement="agreement" />
                </div>
            </div>

            <!-- Progress Card -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Progreso</dt>
                        <dd class="mt-1">
                            <div class="flex items-center">
                                <div class="mr-3 h-2 flex-1 rounded-full bg-gray-200">
                                    <div class="h-2 rounded-full bg-blue-600" :style="{ width: `{agreement.progress_percentage}%` }"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ agreement.progress_percentage }}%</span>
                            </div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Saldo Pendiente</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            {{ formatCurrency(agreement.remaining_balance) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cuotas Vencidas</dt>
                        <dd class="mt-1 text-lg font-semibold" :class="agreement.overdue_installments_count > 0 ? 'text-red-600' : 'text-green-600'">
                            {{ agreement.overdue_installments_count }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Próximo Vencimiento</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ agreement.next_due_date ? formatDate(agreement.next_due_date) : 'N/A' }}
                        </dd>
                    </div>
                </div>
            </div>

            <!-- Agreement Details -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Basic Information -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Información del Acuerdo</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Apartamento</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ agreement.apartment.full_address }}
                                <div class="mt-1 text-xs text-gray-500" v-if="agreement.apartment.residents?.length">
                                    Residentes: {{ agreement.apartment.residents.map((r) => r.name).join(', ') }}
                                </div>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Monto Total</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(agreement.total_debt_amount) }}</dd>
                        </div>
                        <div v-if="agreement.initial_payment">
                            <dt class="text-sm font-medium text-gray-500">Pago Inicial</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(agreement.initial_payment) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cuota Mensual</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(agreement.monthly_payment) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Número de Cuotas</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ agreement.installments }} cuotas</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Período</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(agreement.start_date) }} - {{ formatDate(agreement.end_date) }}</dd>
                        </div>
                        <div v-if="agreement.penalty_rate">
                            <dt class="text-sm font-medium text-gray-500">Tasa de Penalización</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ agreement.penalty_rate }}% mensual</dd>
                        </div>
                    </dl>
                </div>

                <!-- Status Information -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Estado del Acuerdo</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Creado por</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ agreement.created_by }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(agreement.created_at) }}</dd>
                        </div>
                        <div v-if="agreement.approved_at">
                            <dt class="text-sm font-medium text-gray-500">Aprobado por</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ agreement.approved_by }}</dd>
                        </div>
                        <div v-if="agreement.approved_at">
                            <dt class="text-sm font-medium text-gray-500">Fecha de Aprobación</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(agreement.approved_at) }}</dd>
                        </div>
                        <div v-if="agreement.notes">
                            <dt class="text-sm font-medium text-gray-500">Notas</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ agreement.notes }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Installments -->
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Cronograma de Pagos</h3>
                        <button
                            v-if="agreement.status === 'active'"
                            @click="showPaymentModal = true"
                            class="inline-flex items-center rounded-lg border border-transparent bg-blue-600 px-3 py-2 text-sm leading-4 font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        >
                            <CreditCard class="mr-2 h-4 w-4" />
                            Registrar Pago
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Cuota</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Fecha Vencimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Pagado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Fecha Pago</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="installment in agreement.installment_items"
                                :key="installment.id"
                                :class="installment.is_overdue ? 'bg-red-50' : ''"
                            >
                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                    Cuota {{ installment.installment_number }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                    {{ formatCurrency(installment.amount) }}
                                    <div v-if="installment.penalty_amount > 0" class="text-xs text-red-600">
                                        + {{ formatCurrency(installment.penalty_amount) }} (mora)
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                    {{ formatDate(installment.due_date) }}
                                    <div v-if="installment.days_overdue > 0" class="text-xs text-red-600">
                                        {{ installment.days_overdue }} días de retraso
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            installment.status_badge.class,
                                        ]"
                                    >
                                        {{ installment.status_badge.text }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                    {{ formatCurrency(installment.paid_amount) }}
                                    <div v-if="installment.remaining_amount > 0" class="text-xs text-gray-500">
                                        Pendiente: {{ formatCurrency(installment.remaining_amount) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                    {{ installment.paid_date ? formatDate(installment.paid_date) : '-' }}
                                    <div v-if="installment.payment_method" class="text-xs text-gray-500">
                                        {{ installment.payment_method }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Términos y Condiciones</h3>
                <div class="prose prose-sm whitespace-pre-line text-gray-700">
                    {{ agreement.terms_and_conditions }}
                </div>
            </div>

            <!-- Payment Modal -->
            <PaymentModal v-if="showPaymentModal" :agreement="agreement" @close="showPaymentModal = false" />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import ActionMenu from '@/components/PaymentAgreements/ActionMenu.vue';
import PaymentModal from '@/components/PaymentAgreements/PaymentModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/utils/format';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    agreement: Object,
});

const showPaymentModal = ref(false);
const breadcrumbs = [
    {
        title: 'Escritorio',
        href: '/dashboard',
    },
    {
        title: 'Acuerdos de Pagos',
        href: '/payment-agreements/show',
    },
];
</script>
