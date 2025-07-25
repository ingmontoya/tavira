<template>
    <div v-if="hasSavedTour && !isTourActive" class="fixed right-4 bottom-4 z-40 max-w-sm rounded-lg border border-gray-200 bg-white p-4 shadow-lg">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                    <Icon name="map" class="h-4 w-4 text-blue-600" />
                </div>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">Tour en progreso</p>
                <p class="mt-1 text-xs text-gray-500">Paso {{ savedTourStep + 1 }} de {{ tourSteps.length }} - Tour de configuración</p>
                <div class="mt-3 flex space-x-2">
                    <button
                        @click="continueTourOnCurrentPage"
                        class="inline-flex items-center rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        Continuar
                    </button>
                    <button
                        @click="clearTourState"
                        class="inline-flex items-center rounded bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
            <button @click="clearTourState" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                <Icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useFlow1Tour } from '@/composables/useFlow1Tour';
import { useTourState } from '@/composables/useTourState';
import { onMounted, onUnmounted, ref } from 'vue';
import Icon from './Icon.vue';

const { hasSavedTour, savedTourStep, clearTourState, continueTourOnCurrentPage } = useTourState();
const { tourSteps } = useFlow1Tour();
const isTourActive = ref(false);

// Detectar si hay un tour activo en el DOM
const checkTourActive = () => {
    const tourElement = document.querySelector('.virtual-tour');
    isTourActive.value = !!tourElement;
};

let intervalId: number;

onMounted(() => {
    checkTourActive();
    // Verificar periódicamente si el tour está activo
    intervalId = setInterval(checkTourActive, 500);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>
