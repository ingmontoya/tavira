<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-green-50 via-blue-50 to-indigo-50 px-4">
        <div class="w-full max-w-2xl">
            <div class="rounded-2xl bg-white p-8 text-center shadow-xl">
                <!-- Success Icon -->
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <!-- Success Message -->
                <h1 class="mb-4 text-3xl font-bold text-gray-900">¡Pago Exitoso!</h1>

                <div v-if="transaction">
                    <p class="mb-8 text-lg text-gray-600">Tu suscripción a Tavira ha sido activada exitosamente.</p>

                    <!-- Transaction Details -->
                    <div class="mb-8 rounded-lg bg-gray-50 p-6 text-left">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Detalles de la Transacción</h3>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-700">ID de Transacción</p>
                                <p class="font-mono text-sm text-gray-900">{{ transaction.id }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Referencia</p>
                                <p class="font-mono text-sm text-gray-900">{{ transaction.reference }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Método de Pago</p>
                                <p class="text-sm text-gray-900">{{ getPaymentMethodName(transaction.payment_method_type) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Monto Pagado</p>
                                <p class="text-sm font-semibold text-gray-900">${{ formatPrice(transaction.amount_in_cents / 100) }} COP</p>
                            </div>
                        </div>

                        <div v-if="subscription" class="mt-6 border-t pt-6">
                            <h4 class="text-md mb-3 font-semibold text-gray-900">Información de Suscripción</h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Plan</p>
                                    <p class="text-sm text-gray-900">{{ subscription.plan_display_name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Vence</p>
                                    <p class="text-sm text-gray-900">{{ formatDate(subscription.expires_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="mb-8 text-left">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Próximos Pasos</h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start">
                                <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>Recibirás un correo de confirmación con los detalles de tu suscripción</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>Nuestro equipo se pondrá en contacto contigo para configurar tu cuenta</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>En 24 horas tendrás acceso completo a tu plataforma Tavira</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div v-else>
                    <p class="mb-8 text-lg text-gray-600">
                        {{ message || 'Estamos verificando tu pago. Te notificaremos por correo una vez confirmado.' }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a
                        href="/dashboard"
                        class="flex-1 rounded-lg bg-indigo-600 px-6 py-3 text-center font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Volver al Inicio
                    </a>
                    <a
                        href="mailto:soporte@tavira.co"
                        class="flex-1 rounded-lg border border-gray-300 px-6 py-3 text-center font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Contactar Soporte
                    </a>
                </div>

                <!-- Support -->
                <div class="mt-8 border-t pt-6 text-sm text-gray-600">
                    <p>
                        ¿Tienes preguntas? Contáctanos en
                        <a href="mailto:soporte@tavira.co" class="text-indigo-600 hover:text-indigo-500"> soporte@tavira.co </a>
                        o llama al
                        <a href="tel:+5713001234567" class="text-indigo-600 hover:text-indigo-500"> +57 1 300 123 4567 </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    transaction?: any;
    subscription?: any;
    tenant?: any;
    message?: string;
}>();

const getPaymentMethodName = (method: string): string => {
    const methods: Record<string, string> = {
        CARD: 'Tarjeta de Crédito/Débito',
        NEQUI: 'Nequi',
        PSE: 'PSE - Pagos Seguros en Línea',
        BANCOLOMBIA_COLLECT: 'Corresponsal Bancolombia',
        BANCOLOMBIA_TRANSFER: 'Transferencia Bancolombia',
    };
    return methods[method] || method;
};

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('es-CO').format(price);
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
