<template>
    <section id="benefits" class="bg-gradient-to-br from-[#F5F5F5] to-white px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-3xl font-bold text-[#1D3557] md:text-4xl lg:text-5xl">
                    Resultados que
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#FF6B6B] bg-clip-text text-transparent"> Impactan </span>
                </h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-700 md:text-xl">
                    Más de 500 conjuntos en Colombia ya confían en Tavira. Con aplicaciones móviles nativas para iOS y Android, estos son los
                    resultados reales que obtienen nuestros clientes.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 md:gap-8 lg:grid-cols-4">
                <div
                    v-for="(benefit, index) in benefits"
                    :key="index"
                    class="rounded-2xl border-l-4 border-[#06D6A0] bg-white p-6 text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-xl md:p-8"
                >
                    <div class="mb-4 text-4xl">{{ benefit.icon }}</div>
                    <div class="mb-2 text-2xl font-bold text-[#1D3557] md:text-3xl">{{ benefit.percentage }}</div>
                    <h3 class="mb-3 text-lg font-bold text-[#1D3557] md:text-xl">{{ benefit.title }}</h3>
                    <p class="text-gray-600">{{ benefit.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const benefits = computed(() => [
    {
        title: 'Tiempo Ahorrado',
        description: 'Reducción en tiempo dedicado a tareas administrativas rutinarias',
        icon: '⏰',
        percentage: '70%',
    },
    {
        title: 'Reducción de Costos',
        description: 'Menos gastos en papelería, correo físico y procesos manuales',
        icon: '💸',
        percentage: '45%',
    },
    {
        title: 'Satisfacción Residentes',
        description: 'Mayor satisfacción gracias a comunicación clara y procesos eficientes',
        icon: '😊',
        percentage: '85%',
    },
    {
        title: 'Transparencia',
        description: 'Total visibilidad de finanzas, gastos y decisiones del conjunto',
        icon: '🔍',
        percentage: '95%',
    },
]);
</script>
