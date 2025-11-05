<template>
  <section id="red-tavira" class="bg-gradient-to-br from-accent via-accent to-secondary px-6 py-16 md:py-24">
    <div class="mx-auto max-w-7xl">
      <!-- Badge -->
      <div class="mb-6 text-center">
        <span
          class="inline-flex items-center space-x-2 rounded-full border-2 border-white/40 bg-white/25 px-6 py-3 text-white shadow-lg backdrop-blur-md"
        >
          <span class="text-base font-bold">{{ $t('redTavira.badge') }}</span>
        </span>
      </div>

      <!-- Title -->
      <h2 class="mb-6 text-center font-brand text-3xl font-bold text-white md:text-4xl lg:text-5xl">
        {{ $t('redTavira.title') }}
      </h2>

      <!-- Subtitle -->
      <p class="mx-auto mb-12 max-w-4xl text-center text-lg text-white/90 md:text-xl">
        {{ $t('redTavira.subtitle') }}
      </p>

      <!-- How It Works -->
      <div class="mb-12">
        <h3 class="mb-8 text-center text-2xl font-bold text-white md:text-3xl">
          {{ $t('redTavira.howItWorks') }}
        </h3>

        <div class="grid gap-8 md:grid-cols-3">
          <div
            v-for="step in steps"
            :key="step.number"
            class="relative rounded-2xl bg-white/10 p-6 backdrop-blur-md transition-all duration-300 hover:bg-white/15"
          >
            <div
              class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-xl font-bold text-white"
            >
              {{ step.number }}
            </div>
            <h4 class="mb-3 text-xl font-bold text-white">{{ step.title }}</h4>
            <p class="text-white/80">{{ step.description }}</p>
          </div>
        </div>
      </div>

      <!-- Real Case -->
      <div class="mb-12 rounded-2xl bg-white/10 p-8 backdrop-blur-md">
        <div class="mb-4">
          <span class="inline-block rounded-full bg-primary px-4 py-2 text-sm font-bold text-white">
            {{ $t('redTavira.realCase.badge') }}
          </span>
        </div>
        <p class="text-lg italic text-white md:text-xl">
          {{ $t('redTavira.realCase.story') }}
        </p>
      </div>

      <!-- Stats -->
      <div class="grid gap-6 md:grid-cols-3">
        <div
          v-for="(stat, index) in stats"
          :key="index"
          class="rounded-xl bg-white/10 p-6 text-center backdrop-blur-md"
        >
          <div class="text-2xl font-bold text-white md:text-3xl">{{ stat }}</div>
        </div>
      </div>

      <!-- CTA -->
      <div class="mt-12 text-center">
        <button
          class="transform rounded-xl bg-primary px-8 py-4 text-lg font-semibold text-white shadow-2xl transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:bg-primary-700"
        >
          {{ $t('redTavira.cta') }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const steps = computed(() => [
  {
    number: t('redTavira.step1.number'),
    title: t('redTavira.step1.title'),
    description: t('redTavira.step1.description'),
  },
  {
    number: t('redTavira.step2.number'),
    title: t('redTavira.step2.title'),
    description: t('redTavira.step2.description'),
  },
  {
    number: t('redTavira.step3.number'),
    title: t('redTavira.step3.title'),
    description: t('redTavira.step3.description'),
  },
])

const stats = computed(() => [
  t('redTavira.stats.speed'),
  t('redTavira.stats.reduction'),
  t('redTavira.stats.satisfaction'),
])
</script>
