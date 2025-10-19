<template>
    <section id="features" class="bg-[#F5F5F5] px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-4xl font-bold text-gray-900 lg:text-5xl">
                    Todo lo que necesitas para una
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#1D3557] bg-clip-text text-transparent"> administración </span>
                    exitosa
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    Sistema integral diseñado especificamente para conjuntos residenciales en Colombia. Desde finanzas hasta comunicación, todo en una
                    plataforma segura.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-xl border border-[#06D6A0]/20 bg-[#F5F5F5] text-2xl">
                        {{ feature.icon }}
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-gray-900">{{ feature.title }}</h3>
                    <p class="leading-relaxed text-gray-600">{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const features = computed(() => [
    {
        icon: '🏢',
        title: 'Gestión Integral',
        description: 'Administra residentes, apartamentos, personal y proveedores desde un solo lugar. Sistema flexible que se adapta a tu conjunto.',
    },
    {
        icon: '💰',
        title: 'Finanzas Completas',
        description:
            'Contabilidad doble partida, facturación automatizada (opcional DIAN), presupuestos, reportes financieros y cumplimiento normativo.',
    },
    {
        icon: '📢',
        title: 'Comunicación Institucional',
        description: 'Sistema de correos institucional para preservar la memoria del conjunto. Templates personalizables y comunicación efectiva.',
    },
    {
        icon: '🔐',
        title: 'Máxima Seguridad',
        description: 'Base de datos exclusiva por conjunto, encriptación bancaria, backups automatizados y cumplimiento normativo colombiano.',
    },
    {
        icon: '✅',
        title: 'Flujo de Aprobaciones',
        description: 'Workflow completo: Proveedores → Concejo → Administración. Control total de gastos y decisiones con trazabilidad.',
    },
    {
        icon: '🛍️',
        title: 'Comercio Comunitario',
        description: 'Plataforma de confianza entre vecinos. Compra-venta entre residentes con sistema de calificaciones y experiencias.',
    },
    {
        icon: '📱',
        title: 'Apps Móviles Nativas',
        description: 'App para residentes (iOS/Android) y app para portería con citófonos, gestión de paquetes y pases temporales QR.',
    },
    {
        icon: '📊',
        title: 'Reportes Inteligentes',
        description: 'Dashboard ejecutivo, reportes financieros, análisis de morosidad, indicadores de gestión y métricas clave.',
    },
]);
</script>
