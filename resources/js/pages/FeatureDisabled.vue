<template>
  <Head title="Módulo No Disponible" />

  <AppLayout>
    <div class="min-h-screen bg-gray-100 py-12">
      <div class="mx-auto max-w-lg">
        <div class="rounded-lg bg-white p-8 text-center shadow">
          <div class="mx-auto mb-4 h-16 w-16 rounded-full bg-yellow-100 flex items-center justify-center">
            <Icon name="alert-triangle" class="h-8 w-8 text-yellow-600" />
          </div>
          
          <h1 class="mb-4 text-xl font-semibold text-gray-900">
            Módulo No Disponible
          </h1>
          
          <p class="mb-6 text-gray-600">
            {{ message }}
          </p>
          
          <div class="space-y-3">
            <button
              v-if="upgrade_url"
              @click="$inertia.get(upgrade_url)"
              class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 transition-colors"
            >
              Actualizar Plan
            </button>
            
            <button
              @click="$inertia.get('/')"
              class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Volver al Inicio
            </button>
          </div>
          
          <div class="mt-6 text-sm text-gray-500">
            <p>Módulo: <code class="rounded bg-gray-100 px-1 py-0.5">{{ feature }}</code></p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

interface Props {
  feature: string
  message: string
  upgrade_url?: string
}

defineProps<Props>()
</script>