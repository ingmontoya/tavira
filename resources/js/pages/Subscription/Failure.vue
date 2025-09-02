<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 flex items-center justify-center px-4">
    <div class="max-w-2xl w-full">
      <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <!-- Error Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
          <svg class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>

        <!-- Error Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          Pago No Completado
        </h1>
        
        <p class="text-lg text-gray-600 mb-8">
          No pudimos procesar tu pago. Esto puede deberse a varios motivos.
        </p>

        <!-- Transaction Info -->
        <div v-if="reference || transactionId" class="bg-gray-50 rounded-lg p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Transacción</h3>
          <div class="text-left space-y-2">
            <div v-if="reference">
              <p class="text-sm font-medium text-gray-700">Referencia</p>
              <p class="text-sm text-gray-900 font-mono">{{ reference }}</p>
            </div>
            <div v-if="transactionId">
              <p class="text-sm font-medium text-gray-700">ID de Transacción</p>
              <p class="text-sm text-gray-900 font-mono">{{ transactionId }}</p>
            </div>
          </div>
        </div>

        <!-- Common Reasons -->
        <div class="text-left mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Posibles Causas</h3>
          <ul class="space-y-3 text-sm text-gray-700">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>Fondos insuficientes en la cuenta o tarjeta</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>Datos incorrectos de la tarjeta o método de pago</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>Restricciones del banco emisor</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>El enlace de pago expiró</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-orange-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>Problemas temporales de conexión</span>
            </li>
          </ul>
        </div>

        <!-- Next Steps -->
        <div class="text-left mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Qué puedes hacer?</h3>
          <ul class="space-y-3 text-sm text-gray-700">
            <li class="flex items-start">
              <svg class="h-5 w-5 text-indigo-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>Verifica los datos de tu tarjeta o método de pago</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-indigo-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>Contacta a tu banco para autorizar la transacción</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-indigo-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>Intenta con un método de pago diferente</span>
            </li>
            <li class="flex items-start">
              <svg class="h-5 w-5 text-indigo-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>Si el problema persiste, contáctanos para asistencia</span>
            </li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4">
          <a
            href="/subscription/plans"
            class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors text-center"
          >
            Intentar Nuevamente
          </a>
          <a
            href="mailto:soporte@tavira.co"
            class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center"
          >
            Contactar Soporte
          </a>
        </div>

        <!-- Support -->
        <div class="mt-8 pt-6 border-t text-sm text-gray-600">
          <p>
            ¿Necesitas ayuda inmediata? Contáctanos en 
            <a href="mailto:soporte@tavira.co" class="text-indigo-600 hover:text-indigo-500">
              soporte@tavira.co
            </a>
            o llama al 
            <a href="tel:+5713001234567" class="text-indigo-600 hover:text-indigo-500">
              +57 1 300 123 4567
            </a>
          </p>
          <p class="mt-2">
            <strong>Horario de atención:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM (COT)
          </p>
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