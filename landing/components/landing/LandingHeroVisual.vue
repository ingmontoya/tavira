<template>
  <div
    :class="[
      'relative transition-all delay-300 duration-1000',
      isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0',
    ]"
  >
    <div class="relative px-8 py-6 pb-12 pt-8">
      <!-- Red Tavira Network Visualization -->
      <div class="rounded-2xl border border-white/20 bg-white/10 p-6 shadow-2xl backdrop-blur-lg">
        <div class="mb-4 flex items-center justify-between">
          <div class="text-sm font-semibold text-white">🔗 Red Tavira - En Tiempo Real</div>
          <div class="flex items-center space-x-2">
            <div class="h-2 w-2 animate-pulse rounded-full bg-green-400"></div>
            <span class="text-xs text-white/70">500+ Conectados</span>
          </div>
        </div>

        <!-- Network Map -->
        <div class="relative h-48 rounded-lg border border-white/10 bg-gradient-to-br from-blue-900/40 to-purple-900/40 p-4">
          <!-- Central Hub -->
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
            <div class="relative">
              <div class="h-16 w-16 animate-pulse rounded-full bg-gradient-to-r from-secondary to-accent shadow-lg shadow-secondary/50"></div>
              <div class="absolute inset-0 animate-ping rounded-full bg-secondary/30"></div>
            </div>
          </div>

          <!-- Connected Nodes -->
          <div class="absolute top-4 left-8 animate-bounce" style="animation-delay: 0.1s">
            <div class="h-10 w-10 rounded-full bg-green-400/80 shadow-lg"></div>
          </div>
          <div class="absolute top-8 right-12 animate-bounce" style="animation-delay: 0.2s">
            <div class="h-8 w-8 rounded-full bg-blue-400/80 shadow-lg"></div>
          </div>
          <div class="absolute bottom-8 left-12 animate-bounce" style="animation-delay: 0.3s">
            <div class="h-10 w-10 rounded-full bg-purple-400/80 shadow-lg"></div>
          </div>
          <div class="absolute bottom-6 right-8 animate-bounce" style="animation-delay: 0.4s">
            <div class="h-8 w-8 rounded-full bg-pink-400/80 shadow-lg"></div>
          </div>
          <div class="absolute top-1/2 right-4 animate-bounce" style="animation-delay: 0.5s">
            <div class="h-10 w-10 rounded-full bg-teal-400/80 shadow-lg"></div>
          </div>
          <div class="absolute top-1/2 left-4 animate-bounce" style="animation-delay: 0.6s">
            <div class="h-8 w-8 rounded-full bg-orange-400/80 shadow-lg"></div>
          </div>

          <!-- Connection Lines -->
          <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <line x1="50%" y1="50%" x2="15%" y2="20%" stroke="rgba(255,255,255,0.2)" stroke-width="2" stroke-dasharray="5,5">
              <animate attributeName="stroke-dashoffset" from="0" to="10" dur="1s" repeatCount="indefinite"/>
            </line>
            <line x1="50%" y1="50%" x2="85%" y2="25%" stroke="rgba(255,255,255,0.2)" stroke-width="2" stroke-dasharray="5,5">
              <animate attributeName="stroke-dashoffset" from="0" to="10" dur="1s" repeatCount="indefinite"/>
            </line>
            <line x1="50%" y1="50%" x2="20%" y2="75%" stroke="rgba(255,255,255,0.2)" stroke-width="2" stroke-dasharray="5,5">
              <animate attributeName="stroke-dashoffset" from="0" to="10" dur="1s" repeatCount="indefinite"/>
            </line>
            <line x1="50%" y1="50%" x2="80%" y2="80%" stroke="rgba(255,255,255,0.2)" stroke-width="2" stroke-dasharray="5,5">
              <animate attributeName="stroke-dashoffset" from="0" to="10" dur="1s" repeatCount="indefinite"/>
            </line>
          </svg>
        </div>

        <!-- Stats Grid -->
        <div class="mt-4 grid grid-cols-3 gap-3">
          <div class="rounded-lg border border-green-400/30 bg-green-400/10 p-3 text-center">
            <div class="text-xs text-white/70">Alertas Hoy</div>
            <div class="text-xl font-bold text-green-400">24</div>
          </div>
          <div class="rounded-lg border border-blue-400/30 bg-blue-400/10 p-3 text-center">
            <div class="text-xs text-white/70">Resp. Promedio</div>
            <div class="text-xl font-bold text-blue-400">28s</div>
          </div>
          <div class="rounded-lg border border-purple-400/30 bg-purple-400/10 p-3 text-center">
            <div class="text-xs text-white/70">Capturados</div>
            <div class="text-xl font-bold text-purple-400">12</div>
          </div>
        </div>
      </div>

      <!-- Floating Alert Card -->
      <div class="absolute -top-4 -right-2 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 p-4 shadow-xl">
        <div class="flex items-center space-x-2">
          <span class="text-2xl">🚨</span>
          <div>
            <div class="text-sm font-bold text-white">Alerta Activa</div>
            <div class="text-xs text-white/80">2.3km - Hace 30s</div>
          </div>
        </div>
      </div>

      <!-- Floating Success Card -->
      <div class="absolute -bottom-4 -left-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 p-4 shadow-xl">
        <div class="flex items-center space-x-2">
          <span class="text-2xl">✅</span>
          <div>
            <div class="text-sm font-bold text-white">Captura Exitosa</div>
            <div class="text-xs text-white/80">Hace 2 min</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  isVisible: boolean
}>()
</script>
