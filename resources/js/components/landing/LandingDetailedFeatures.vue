<template>
    <section id="detailed-features" class="bg-white px-6 py-16 md:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="mb-6 text-3xl md:text-4xl lg:text-5xl font-bold text-[#1D3557]">
                    Funcionalidades
                    <span class="bg-gradient-to-r from-[#06D6A0] to-[#1D3557] bg-clip-text text-transparent">
                        Detalladas
                    </span>
                    para cada Usuario
                </h2>
                <p class="mx-auto max-w-3xl text-lg md:text-xl text-gray-700">
                    Desde aplicaciones móviles nativas hasta gestión avanzada, cada usuario tiene herramientas específicas para sus necesidades.
                </p>
            </div>

            <div class="grid gap-6 md:gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="rounded-2xl border border-gray-200 bg-white p-6 md:p-8 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-[#06D6A0]/30"
                >
                    <div class="mb-6 text-4xl">{{ feature.icon }}</div>
                    <h3 class="mb-3 text-xl md:text-2xl font-bold text-[#1D3557]">{{ feature.title }}</h3>
                    <p class="mb-6 text-gray-600">{{ feature.description }}</p>
                    <ul class="space-y-2">
                        <li v-for="(item, idx) in feature.features" :key="idx" class="flex items-center space-x-3">
                            <svg class="h-5 w-5 text-[#06D6A0]" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-gray-700">{{ item }}</span>
                        </li>
                    </ul>
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
        icon: '📱',
        title: 'App para Residentes',
        description: 'Aplicación nativa iOS y Android con todas las funcionalidades que necesita cada residente.',
        features: [
            'Estado de cuenta en tiempo real',
            'Pagos en línea seguros',
            'Enviar pases QR a visitas',
            'Marketplace comunitario',
            'Reservar espacios comunes',
            'Recibir comunicados instantáneos'
        ],
    },
    {
        icon: '🏢',
        title: 'App para Portería',
        description: 'Herramienta profesional para el control total de acceso y gestión de seguridad.',
        features: [
            'Citófonos integrados al celular',
            'Escaneo automático de QR',
            'Gestión de paquetes con fotos',
            'Registro de visitas y proveedores',
            'Control de acceso vehicular',
            'Historial completo de accesos'
        ],
    },
    {
        icon: '💳',
        title: 'Sistema Financiero',
        description: 'Contabilidad profesional que cumple con toda la normatividad colombiana.',
        features: [
            'Contabilidad doble partida',
            'Facturación DIAN (opcional)',
            'Conciliación bancaria automática',
            'Reportes ejecutivos completos',
            'Control de morosidad',
            'Presupuestos anuales'
        ],
    },
    {
        icon: '✅',
        title: 'Flujo de Aprobaciones',
        description: 'Control total de procesos desde proveedores hasta ejecución con trazabilidad completa.',
        features: [
            'Workflow Proveedor → Concejo → Admin',
            'Trazabilidad 100% de decisiones',
            'Notificaciones automáticas',
            'Control presupuestal integrado',
            'Historial permanente',
            'Aprobaciones por niveles'
        ],
    },
    {
        icon: '📧',
        title: 'Correos Institucionales',
        description: 'Sistema de emails que pertenece al conjunto, garantizando continuidad institucional.',
        features: [
            'Direcciones @tuconjunto.com',
            'Memoria institucional permanente',
            'Independiente de administraciones',
            'Templates oficiales',
            'Historial completo conservado',
            'Comunicación profesional'
        ],
    },
    {
        icon: '🛍️',
        title: 'Marketplace Comunitario',
        description: 'Plataforma de confianza entre vecinos con sistema de calificaciones y experiencias.',
        features: [
            'Compra-venta entre residentes',
            'Sistema de calificaciones reales',
            'Servicios profesionales',
            'Solo residentes verificados',
            'Categorías variadas',
            'Ambiente de confianza mutua'
        ],
    },
]);
</script>
