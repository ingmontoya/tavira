<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import axios from 'axios';

interface Plan {
    key: string;
    name: string;
    price: number;
    price_display: string;
    leads_per_month: number;
    commission_rate: number;
    status: string;
    badge?: string;
    priority: string;
    support: string;
    insignia?: string;
    exclusive_b2b2c?: boolean;
    account_manager?: boolean;
    features: string[];
}

interface Props {
    provider: any;
    mustSelectPlan: boolean;
    plans: Record<string, Plan>;
    currentPlan: string;
}

const props = defineProps<Props>();

const processing = ref(false);

// Continuar sin plan (mientras los planes están temporalmente deshabilitados)
const continuarSinPlan = async () => {
    processing.value = true;

    try {
        // Marcar que el proveedor ha visto el pricing
        await axios.post('/provider/pricing-viewed');

        // Redirigir al dashboard
        router.visit('/provider/dashboard');
    } catch (error) {
        console.error('Error al continuar:', error);
    } finally {
        processing.value = false;
    }
};

// Para cuando se activen los planes
const selectPlan = async (plan: Plan) => {
    if (plan.status === 'temporarily_unavailable') {
        return; // No permitir selección mientras esté tachado
    }

    processing.value = true;

    try {
        await axios.post('/provider/subscribe', {
            plan: plan.key
        });

        // Redirigir al proceso de pago
        router.visit('/provider/payment', {
            data: { plan: plan.key }
        });
    } catch (error) {
        console.error('Error al seleccionar plan:', error);
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <!-- Backdrop oscuro, no se puede cerrar -->
    <div class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
        <!-- Container del modal -->
        <div class="bg-white rounded-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-8 text-white text-center rounded-t-2xl">
                <h1 class="text-3xl font-bold mb-2">Elige tu Plan de Proveedor</h1>
                <p class="text-blue-100">Accede a miles de oportunidades de negocio en conjuntos residenciales</p>
                <div class="mt-4 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full inline-block font-semibold">
                    🎉 OFERTA DE LANZAMIENTO - TODOS LOS PLANES 50% OFF
                </div>
            </div>

            <!-- Grid de planes -->
            <div class="p-8">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Plan Básico -->
                    <div class="relative border-2 border-gray-200 rounded-xl p-6 opacity-75">
                        <div class="absolute inset-0 bg-gray-100/50 rounded-xl"></div>
                        <div class="relative z-10">
                            <div class="line-through decoration-red-500 decoration-4">
                                <h3 class="text-xl font-bold mb-2">{{ plans.basico.name }}</h3>
                                <p class="text-3xl font-bold text-gray-600">{{ plans.basico.price_display }}</p>
                            </div>
                            <div class="absolute top-0 right-0 bg-red-500 text-white px-3 py-1 rounded-full text-xs">
                                NO DISPONIBLE
                            </div>
                            <!-- Features visibles pero tachadas -->
                            <ul class="mt-6 space-y-3 text-gray-500 line-through">
                                <li v-for="feature in plans.basico.features" :key="feature">
                                    ✓ {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Plan Profesional - Destacado -->
                    <div class="relative border-2 border-blue-500 rounded-xl p-6 transform scale-105 shadow-xl opacity-75">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-blue-500 text-white px-4 py-1 rounded-full text-sm z-10">
                            {{ plans.profesional.badge }}
                        </div>
                        <div class="absolute inset-0 bg-gray-100/50 rounded-xl"></div>
                        <div class="relative z-10 pt-2">
                            <div class="line-through decoration-red-500 decoration-4">
                                <h3 class="text-xl font-bold mb-2 text-blue-600">{{ plans.profesional.name }}</h3>
                                <p class="text-3xl font-bold">
                                    {{ plans.profesional.price_display }}<span class="text-sm">/mes</span>
                                </p>
                            </div>
                            <div class="absolute top-2 right-0 bg-red-500 text-white px-3 py-1 rounded-full text-xs">
                                NO DISPONIBLE
                            </div>
                            <ul class="mt-6 space-y-3 text-gray-500 line-through">
                                <li v-for="feature in plans.profesional.features" :key="feature">
                                    ✅ {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Plan Premium -->
                    <div class="relative border-2 border-purple-500 rounded-xl p-6 opacity-75">
                        <div class="absolute -top-4 right-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-1 rounded-full text-sm z-10">
                            {{ plans.premium.badge }}
                        </div>
                        <div class="absolute inset-0 bg-gray-100/50 rounded-xl"></div>
                        <div class="relative z-10 pt-2">
                            <div class="line-through decoration-red-500 decoration-4">
                                <h3 class="text-xl font-bold mb-2 text-purple-600">{{ plans.premium.name }}</h3>
                                <p class="text-3xl font-bold">
                                    {{ plans.premium.price_display }}<span class="text-sm">/mes</span>
                                </p>
                            </div>
                            <div class="absolute top-2 right-0 bg-red-500 text-white px-3 py-1 rounded-full text-xs">
                                NO DISPONIBLE
                            </div>
                            <ul class="mt-6 space-y-3 text-gray-500 line-through text-sm">
                                <li v-for="feature in plans.premium.features" :key="feature">
                                    🌟 {{ feature }}
                                </li>
                            </ul>
                            <div class="mt-4 bg-gradient-to-r from-purple-100 to-pink-100 border border-purple-300 rounded-lg p-3">
                                <p class="text-xs text-purple-800 font-semibold line-through">
                                    EXCLUSIVO: Acceso B2B2C
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje temporal -->
                <div class="mt-8 bg-yellow-50 border-2 border-yellow-400 rounded-xl p-6 text-center">
                    <p class="text-lg font-semibold text-gray-800">
                        🚧 Estamos preparando algo especial para ti
                    </p>
                    <p class="text-gray-600 mt-2">
                        Los planes estarán disponibles muy pronto. Mientras tanto, explora la plataforma con acceso limitado.
                    </p>
                    <Button
                        @click="continuarSinPlan"
                        :disabled="processing"
                        class="mt-4 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition"
                    >
                        <span v-if="processing">Cargando...</span>
                        <span v-else>Continuar con Acceso Limitado</span>
                    </Button>
                </div>

                <!-- Información adicional -->
                <div class="mt-8 text-center text-sm text-gray-500">
                    <p>¿Preguntas sobre los planes? Contáctanos en <a href="mailto:soporte@tavira.com.co" class="text-blue-600 hover:underline">soporte@tavira.com.co</a></p>
                </div>
            </div>
        </div>
    </div>
</template>
