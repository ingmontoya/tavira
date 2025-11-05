<template>
  <section id="problem" class="bg-gray-50 px-6 py-16 md:py-24">
    <div class="mx-auto max-w-7xl">
      <h2 class="mb-12 text-center font-brand text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
        {{ $t('problem.title') }}
      </h2>

      <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="(pain, index) in pains"
          :key="index"
          class="rounded-2xl bg-white p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
        >
          <div class="mb-4 text-5xl">{{ pain.icon }}</div>
          <h3 class="mb-3 text-xl font-bold text-gray-900">{{ pain.title }}</h3>
          <p class="text-gray-600">{{ pain.description }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n()

const pains = computed(() => [
  {
    icon: t('problem.pain1.icon'),
    title: t('problem.pain1.title'),
    description: t('problem.pain1.description'),
  },
  {
    icon: t('problem.pain2.icon'),
    title: t('problem.pain2.title'),
    description: t('problem.pain2.description'),
  },
  {
    icon: t('problem.pain3.icon'),
    title: t('problem.pain3.title'),
    description: t('problem.pain3.description'),
  },
  {
    icon: t('problem.pain4.icon'),
    title: t('problem.pain4.title'),
    description: t('problem.pain4.description'),
  },
])
</script>
