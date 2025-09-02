<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <!-- Top Navigation for Authenticated Users -->
    <div v-if="$page.props.auth.user" class="bg-white shadow-sm border-b">
      <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center">
          <h2 class="text-lg font-semibold text-gray-900">Configuración de Suscripción</h2>
        </div>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">{{ $page.props.auth.user.email }}</span>
          <form method="POST" action="/logout" class="inline">
            <input type="hidden" name="_token" :value="$page.props.csrf_token">
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
              Cerrar Sesión
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          Planes de Suscripción Tavira
        </h1>
        <div v-if="$page.props.auth.user" class="bg-blue-50 rounded-lg p-6 mb-6 max-w-3xl mx-auto">
          <div class="flex items-center mb-4">
            <svg class="h-8 w-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-xl font-semibold text-blue-900">
              ¡Bienvenido {{ $page.props.auth.user.name }}!
            </h2>
          </div>
          <p class="text-blue-800">
            Para completar la configuración de tu cuenta y acceder a tu plataforma Tavira, 
            necesitas seleccionar y pagar un plan de suscripción.
          </p>
        </div>
        <p v-else class="text-xl text-gray-600 max-w-3xl mx-auto">
          Gestiona tu conjunto residencial con la plataforma más completa de Colombia.
          Elige el plan que mejor se adapte a tus necesidades.
        </p>
      </div>

      <!-- Billing Toggle -->
      <div class="flex justify-center mb-12">
        <div class="bg-white rounded-lg p-1 shadow-sm border">
          <button
            @click="billingType = 'mensual'"
            :class="[
              'px-6 py-2 rounded-md text-sm font-medium transition-all duration-200',
              billingType === 'mensual'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-gray-700 hover:text-indigo-600'
            ]"
          >
            Mensual
          </button>
          <button
            @click="billingType = 'anual'"
            :class="[
              'px-6 py-2 rounded-md text-sm font-medium transition-all duration-200',
              billingType === 'anual'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-gray-700 hover:text-indigo-600'
            ]"
          >
            Anual
            <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
              Ahorra 10%
            </span>
          </button>
        </div>
      </div>

      <!-- Plans Grid -->
      <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <div
          v-for="plan in plans"
          :key="plan.id"
          :class="[
            'relative bg-white rounded-2xl shadow-lg border-2 transition-all duration-300 hover:shadow-xl',
            plan.popular
              ? 'border-indigo-500 ring-2 ring-indigo-500 ring-opacity-20'
              : 'border-gray-200 hover:border-indigo-300'
          ]"
        >
          <!-- Popular Badge -->
          <div
            v-if="plan.popular"
            class="absolute -top-4 left-1/2 transform -translate-x-1/2"
          >
            <span class="bg-indigo-600 text-white px-6 py-1 rounded-full text-sm font-medium">
              Más Popular
            </span>
          </div>

          <div class="p-8">
            <!-- Plan Header -->
            <div class="text-center mb-8">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
              <div class="mb-4">
                <span class="text-4xl font-bold text-gray-900">
                  ${{ formatPrice(getPrice(plan)) }}
                </span>
                <span class="text-gray-600 ml-1">
                  /{{ billingType === 'anual' ? 'año' : 'mes' }}
                </span>
              </div>
              <p v-if="billingType === 'anual'" class="text-sm text-green-600 font-medium">
                Equivale a ${{ formatPrice(Math.round(getPrice(plan) / 12)) }}/mes
              </p>
            </div>

            <!-- Features -->
            <ul class="space-y-4 mb-8">
              <li v-for="feature in plan.features" :key="feature" class="flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700">{{ feature }}</span>
              </li>
            </ul>

            <!-- CTA Button -->
            <button
              @click="selectPlan(plan)"
              :class="[
                'w-full py-3 px-6 rounded-lg font-medium transition-all duration-200',
                plan.popular
                  ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md hover:shadow-lg'
                  : 'bg-gray-100 text-gray-900 hover:bg-gray-200 border border-gray-300'
              ]"
              :disabled="loading"
            >
              <span v-if="loading && selectedPlan?.id === plan.id">
                Procesando...
              </span>
              <span v-else>
                Seleccionar Plan
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Contact Section -->
      <div class="text-center mt-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">
          ¿Necesitas un plan personalizado?
        </h3>
        <p class="text-gray-600 mb-6">
          Contacta con nuestro equipo para conjuntos con necesidades especiales
        </p>
        <a
          href="mailto:contacto@tavira.co"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-200"
        >
          Contactar Ventas
        </a>
      </div>
    </div>

    <!-- Subscription Modal -->
    <SubscriptionModal
      v-if="showModal"
      :plan="selectedPlan"
      :billing-type="billingType"
      :price="selectedPlan ? getPrice(selectedPlan) : 0"
      :user="$page.props.auth.user"
      @close="showModal = false"
      @submit="handleSubscription"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SubscriptionModal from './SubscriptionModal.vue';

interface Plan {
  id: string;
  name: string;
  price: number;
  billing: string;
  features: string[];
  popular: boolean;
}

const props = defineProps<{
  plans: Plan[];
}>();

const billingType = ref<'mensual' | 'anual'>('mensual');
const showModal = ref(false);
const selectedPlan = ref<Plan | null>(null);
const loading = ref(false);

const getPrice = (plan: Plan): number => {
  const basePrice = plan.price;
  if (billingType.value === 'anual') {
    // 10% discount for annual billing
    return Math.round(basePrice * 12 * 0.9);
  }
  return basePrice;
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('es-CO').format(price);
};

const selectPlan = (plan: Plan) => {
  selectedPlan.value = plan;
  showModal.value = true;
};

const handleSubscription = async (formData: any) => {
  loading.value = true;
  
  try {
    const response = await fetch('/subscription/payment-link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        plan_id: selectedPlan.value?.id,
        billing_type: billingType.value,
        ...formData,
      }),
    });

    const result = await response.json();

    if (result.success) {
      // Redirect to payment link
      window.location.href = result.payment_link;
    } else {
      alert('Error creando el enlace de pago: ' + result.message);
    }
  } catch (error) {
    console.error('Error creating payment link:', error);
    alert('Error interno del servidor');
  } finally {
    loading.value = false;
    showModal.value = false;
  }
};
</script>