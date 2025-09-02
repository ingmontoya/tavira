<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">
              Suscripción a {{ plan?.name }}
            </h3>
            <p class="text-lg text-indigo-600 font-semibold">
              ${{ formatPrice(price) }}/{{ billingType === 'anual' ? 'año' : 'mes' }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Conjunto Information -->
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nombre del Conjunto *
              </label>
              <input
                v-model="form.conjunto_name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ej: Conjunto Residencial Los Pinos"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Dirección del Conjunto
              </label>
              <input
                v-model="form.conjunto_address"
                type="text"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ej: Calle 123 #45-67, Bogotá"
              >
            </div>
          </div>

          <!-- Contact Information -->
          <div class="border-t pt-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">
              Información de Contacto
            </h4>
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Nombre del Administrador *
                </label>
                <input
                  v-model="form.customer_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Nombre completo"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Teléfono
                </label>
                <input
                  v-model="form.customer_phone"
                  type="tel"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ej: +57 300 123 4567"
                >
              </div>
            </div>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Correo Electrónico *
              </label>
              <input
                v-model="form.customer_email"
                type="email"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="administracion@ejemplo.com"
              >
            </div>
          </div>

          <!-- Location -->
          <div class="border-t pt-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">
              Ubicación
            </h4>
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Ciudad
                </label>
                <select
                  v-model="form.city"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="Bogotá">Bogotá</option>
                  <option value="Medellín">Medellín</option>
                  <option value="Cali">Cali</option>
                  <option value="Barranquilla">Barranquilla</option>
                  <option value="Cartagena">Cartagena</option>
                  <option value="Bucaramanga">Bucaramanga</option>
                  <option value="Pereira">Pereira</option>
                  <option value="Manizales">Manizales</option>
                  <option value="Otra">Otra</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Departamento
                </label>
                <select
                  v-model="form.region"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="Bogotá D.C.">Bogotá D.C.</option>
                  <option value="Antioquia">Antioquia</option>
                  <option value="Valle del Cauca">Valle del Cauca</option>
                  <option value="Atlántico">Atlántico</option>
                  <option value="Bolívar">Bolívar</option>
                  <option value="Santander">Santander</option>
                  <option value="Risaralda">Risaralda</option>
                  <option value="Caldas">Caldas</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Terms -->
          <div class="border-t pt-6">
            <label class="flex items-start">
              <input
                v-model="form.acceptTerms"
                type="checkbox"
                required
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              >
              <span class="ml-3 text-sm text-gray-700">
                Acepto los 
                <a href="/terminos" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                  términos y condiciones
                </a>
                y la 
                <a href="/privacidad" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                  política de privacidad
                </a>
                *
              </span>
            </label>
          </div>

          <!-- Summary -->
          <div class="bg-gray-50 rounded-lg p-6 border-t">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Resumen</h4>
            <div class="flex justify-between items-center mb-2">
              <span>Plan {{ plan?.name }} ({{ billingType }})</span>
              <span class="font-semibold">${{ formatPrice(price) }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span>IVA (19%)</span>
              <span class="font-semibold">${{ formatPrice(Math.round(price * 0.19)) }}</span>
            </div>
            <div class="border-t pt-2 flex justify-between items-center text-lg font-bold">
              <span>Total a Pagar</span>
              <span>${{ formatPrice(Math.round(price * 1.19)) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-4 pt-6">
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="!isFormValid || loading"
              class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="loading">Procesando...</span>
              <span v-else>Continuar al Pago</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
  plan: any;
  billingType: string;
  price: number;
  user?: any;
}>();

const emit = defineEmits<{
  close: [];
  submit: [formData: any];
}>();

const loading = ref(false);

const form = ref({
  conjunto_name: '',
  conjunto_address: '',
  customer_name: props.user?.name || '',
  customer_email: props.user?.email || '',
  customer_phone: '',
  city: 'Bogotá',
  region: 'Bogotá D.C.',
  acceptTerms: false,
});

const isFormValid = computed(() => {
  return form.value.conjunto_name &&
         form.value.customer_name &&
         form.value.customer_email &&
         form.value.acceptTerms;
});

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('es-CO').format(price);
};

const handleSubmit = () => {
  if (!isFormValid.value) return;
  
  loading.value = true;
  emit('submit', form.value);
};
</script>