<template>
  <div 
    v-if="hasSavedTour && !isTourActive" 
    class="fixed bottom-4 right-4 z-40 bg-white rounded-lg shadow-lg border border-gray-200 p-4 max-w-sm"
  >
    <div class="flex items-start space-x-3">
      <div class="flex-shrink-0">
        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
          <Icon name="map" class="w-4 h-4 text-blue-600" />
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900">Tour en progreso</p>
        <p class="text-xs text-gray-500 mt-1">
          Paso {{ savedTourStep + 1 }} de {{ tourSteps.length }} - Tour de configuración
        </p>
        <div class="flex space-x-2 mt-3">
          <button 
            @click="continueTourOnCurrentPage"
            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors"
          >
            Continuar
          </button>
          <button 
            @click="clearTourState"
            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors"
          >
            Cancelar
          </button>
        </div>
      </div>
      <button 
        @click="clearTourState"
        class="flex-shrink-0 text-gray-400 hover:text-gray-600"
      >
        <Icon name="x" class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useTourState } from '@/composables/useTourState'
import { useFlow1Tour } from '@/composables/useFlow1Tour'
import Icon from './Icon.vue'

const { hasSavedTour, savedTourStep, clearTourState, continueTourOnCurrentPage } = useTourState()
const { tourSteps } = useFlow1Tour()
const isTourActive = ref(false)

// Detectar si hay un tour activo en el DOM
const checkTourActive = () => {
  const tourElement = document.querySelector('.virtual-tour')
  isTourActive.value = !!tourElement
}

let intervalId: number

onMounted(() => {
  checkTourActive()
  // Verificar periódicamente si el tour está activo
  intervalId = setInterval(checkTourActive, 500)
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})
</script>