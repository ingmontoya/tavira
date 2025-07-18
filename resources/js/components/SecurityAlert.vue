<template>
  <div v-if="show" :class="alertClasses">
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <component :is="iconComponent" class="h-5 w-5" />
      </div>
      <div class="ml-3">
        <p class="text-sm font-medium">{{ message }}</p>
        <p v-if="description" class="text-sm opacity-90 mt-1">{{ description }}</p>
      </div>
      <div class="ml-auto pl-3">
        <button @click="dismiss" class="inline-flex rounded-md p-1.5 hover:bg-black/10 focus:outline-none focus:ring-2 focus:ring-white/20">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  type?: 'info' | 'warning' | 'error' | 'success'
  message: string
  description?: string
  dismissible?: boolean
  autoHide?: boolean
  duration?: number
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  dismissible: true,
  autoHide: false,
  duration: 5000,
})

const show = ref(true)

const alertClasses = computed(() => ({
  'rounded-md p-4 mb-4': true,
  'bg-blue-50 text-blue-800': props.type === 'info',
  'bg-yellow-50 text-yellow-800': props.type === 'warning',
  'bg-red-50 text-red-800': props.type === 'error',
  'bg-green-50 text-green-800': props.type === 'success',
}))

const iconComponent = computed(() => {
  switch (props.type) {
    case 'info':
      return 'svg'
    case 'warning':
      return 'svg'
    case 'error':
      return 'svg'
    case 'success':
      return 'svg'
    default:
      return 'svg'
  }
})

const dismiss = () => {
  show.value = false
}

if (props.autoHide) {
  setTimeout(dismiss, props.duration)
}
</script>