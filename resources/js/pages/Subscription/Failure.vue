<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 px-4">
        <div class="w-full max-w-2xl">
            <div class="rounded-2xl bg-white p-8 text-center shadow-xl">
                <!-- Error Icon -->
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                <!-- Error Message -->
                <h1 class="mb-4 text-3xl font-bold text-gray-900">Pago No Completado</h1>

                <p class="mb-8 text-lg text-gray-600">No pudimos procesar tu pago. Esto puede deberse a varios motivos.</p>

                <!-- Transaction Info -->
                <div v-if="reference || transactionId" class="mb-8 rounded-lg bg-gray-50 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Información de la Transacción</h3>
                    <div class="space-y-2 text-left">
                        <div v-if="reference">
                            <p class="text-sm font-medium text-gray-700">Referencia</p>
                            <p class="font-mono text-sm text-gray-900">{{ reference }}</p>
                        </div>
                        <div v-if="transactionId">
                            <p class="text-sm font-medium text-gray-700">ID de Transacción</p>
                            <p class="font-mono text-sm text-gray-900">{{ transactionId }}</p>
                        </div>
                    </div>
                </div>

                <!-- Common Reasons -->
                <div class="mb-8 text-left">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Posibles Causas</h3>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Fondos insuficientes en la cuenta o tarjeta</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Datos incorrectos de la tarjeta o método de pago</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Restricciones del banco emisor</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>El enlace de pago expiró</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Problemas temporales de conexión</span>
                        </li>
                    </ul>
                </div>

                <!-- Next Steps -->
                <div class="mb-8 text-left">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">¿Qué puedes hacer?</h3>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Verifica los datos de tu tarjeta o método de pago</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Contacta a tu banco para autorizar la transacción</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Intenta con un método de pago diferente</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="mt-0.5 mr-3 h-5 w-5 flex-shrink-0 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Si el problema persiste, contáctanos para asistencia</span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a
                        href="/subscription/plans"
                        class="flex-1 rounded-lg bg-indigo-600 px-6 py-3 text-center font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Intentar Nuevamente
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
                        ¿Necesitas ayuda inmediata? Contáctanos en
                        <a href="mailto:soporte@tavira.co" class="text-indigo-600 hover:text-indigo-500"> soporte@tavira.co </a>
                        o llama al
                        <a href="tel:+5713001234567" class="text-indigo-600 hover:text-indigo-500"> +57 1 300 123 4567 </a>
                    </p>
                    <p class="mt-2"><strong>Horario de atención:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM (COT)</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    reference?: string;
    transactionId?: string;
}>();
</script>
