<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface Props {
    currentPlan?: string;
    leadsUsed?: number;
    leadsLimit?: number;
    hasSeenPricing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    currentPlan: 'none',
    leadsUsed: 0,
    leadsLimit: 0,
    hasSeenPricing: false,
});

const showUpgrade = computed(() => {
    // Si no ha visto pricing, no mostrar banner (será redirigido al modal)
    if (!props.hasSeenPricing) {
        return false;
    }

    // Premium no necesita upgrade
    if (props.currentPlan === 'premium') {
        return false;
    }

    // Mostrar si está cerca del límite en plan básico
    if (props.currentPlan === 'basico' && props.leadsUsed >= 3) {
        return true;
    }

    // Mostrar si está cerca del límite en plan profesional
    if (props.currentPlan === 'profesional' && props.leadsUsed >= 40) {
        return true;
    }

    // Mostrar si no tiene plan activo
    if (props.currentPlan === 'none') {
        return true;
    }

    return false;
});

const upgradeMessage = computed(() => {
    if (props.currentPlan === 'none') {
        return '🚀 Activa un plan para acceder a más oportunidades';
    }
    if (props.currentPlan === 'basico') {
        return `🚀 Has usado ${props.leadsUsed} de ${props.leadsLimit} leads`;
    }
    if (props.currentPlan === 'profesional') {
        return '💎 Desbloquea acceso a 300K+ propietarios con Premium';
    }
    return '';
});

const upgradeSubtext = computed(() => {
    if (props.currentPlan === 'none') {
        return 'Elige un plan y comienza a recibir solicitudes de cotización';
    }
    if (props.currentPlan === 'basico') {
        return 'Actualiza a Profesional para 50 leads/mes y 8% de comisión';
    }
    if (props.currentPlan === 'profesional') {
        return 'Solo Premium tiene acceso al marketplace B2B2C de propietarios';
    }
    return '';
});

const openPricingModal = () => {
    // Abrir modal de pricing sin bloquear navegación
    router.visit('/provider/pricing?upgrade=true');
};
</script>

<template>
    <div
        v-if="showUpgrade"
        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4 rounded-lg mb-6"
    >
        <div class="flex items-center justify-between flex-col md:flex-row gap-4">
            <div class="text-center md:text-left">
                <h3 class="font-bold text-lg">
                    {{ upgradeMessage }}
                </h3>
                <p class="text-purple-100 text-sm mt-1">
                    {{ upgradeSubtext }}
                </p>
            </div>
            <Button
                @click="openPricingModal"
                class="bg-white text-purple-600 px-6 py-2 rounded-lg font-semibold hover:bg-purple-50 transition whitespace-nowrap"
            >
                Ver Planes
            </Button>
        </div>
    </div>
</template>
