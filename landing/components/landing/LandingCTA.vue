<template>
  <section id="cta" class="bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-20">
    <div class="mx-auto max-w-4xl text-center">
      <div
        class="rounded-3xl border border-white/20 bg-gradient-to-r from-white/10 to-white/5 p-8 backdrop-blur-lg md:p-12"
      >
        <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl lg:text-5xl">
          {{ $t('cta.title').split(' ').slice(0, 2).join(' ') }}
          <span class="text-primary">
            {{ $t('cta.title').split(' ')[2] }}
          </span>
          {{ $t('cta.title').split(' ').slice(3).join(' ') }}
        </h2>
        <p class="mx-auto mb-8 max-w-2xl text-lg text-white/85 md:text-xl">
          {{ $t('cta.description') }}
        </p>

        <div class="flex flex-col justify-center gap-4 sm:flex-row">
          <a
            href="https://tavira.com.co/register"
            class="flex-shrink-0 transform rounded-xl bg-gradient-to-r from-primary to-primary px-4 py-3 text-sm font-semibold text-white shadow-2xl transition-all duration-200 hover:-translate-y-1 hover:from-primary-700 hover:to-primary-700 hover:shadow-primary/25 md:px-8 md:py-4 md:text-lg"
          >
            {{ $t('cta.register') }}
          </a>
          <button
            class="flex-shrink-0 rounded-xl border-2 border-white/30 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:border-white/50 hover:bg-white/15 md:px-8 md:py-4 md:text-lg"
          >
            {{ $t('cta.demo') }}
          </button>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-6 text-sm text-white/60">
          <span v-for="benefit in benefits" :key="benefit" class="flex items-center space-x-2">
            <svg class="h-4 w-4 text-secondary" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
            <span>{{ benefit }}</span>
          </span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const benefits = computed(() => [
  t('cta.benefits.trial'),
  t('cta.benefits.noCard'),
  t('cta.benefits.support'),
  t('cta.benefits.implementation'),
])
</script>
