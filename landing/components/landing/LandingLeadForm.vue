<template>
  <section id="contact" class="bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-20">
    <div class="mx-auto max-w-7xl">
      <div
        class="rounded-3xl border border-white/20 bg-gradient-to-r from-white/10 to-white/5 p-8 backdrop-blur-lg md:p-12"
      >
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
          <!-- Columna Izquierda: Título y descripción -->
          <div class="flex flex-col justify-center space-y-6">
            <h2 class="text-3xl font-bold text-white md:text-4xl lg:text-5xl">
              {{ $t('leadForm.title').split(' ').slice(0, 2).join(' ') }}
              <span class="text-primary">
                {{ $t('leadForm.title').split(' ')[2] }}
              </span>
              {{ $t('leadForm.title').split(' ').slice(3).join(' ') }}
            </h2>
            <p class="text-lg text-white/85 md:text-xl">
              {{ $t('leadForm.description') }}
            </p>

            <!-- Beneficios -->
            <div class="space-y-4 pt-4">
              <div v-for="benefit in benefits" :key="benefit" class="flex items-center space-x-3">
                <svg class="h-5 w-5 flex-shrink-0 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="text-base text-white/80">{{ benefit }}</span>
              </div>
            </div>
          </div>

          <!-- Columna Derecha: Iframe del formulario -->
          <div class="flex items-center justify-center">
            <div
              class="w-full overflow-hidden rounded-2xl border border-white/20 bg-white shadow-2xl"
            >
              <iframe
                src="https://crm.tavira.com.co/forms/wtl/15da533281848176cc8c34e472191b2e"
                class="h-[450px] w-full"
                frameborder="0"
                sandbox="allow-top-navigation allow-forms allow-scripts allow-same-origin allow-popups"
                allowfullscreen
                loading="lazy"
              ></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const benefits = computed(() => [
  t('leadForm.benefits.trial'),
  t('leadForm.benefits.noCard'),
  t('leadForm.benefits.support'),
  t('leadForm.benefits.implementation'),
])
</script>
